<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferValidationResult;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_service_test extends advanced_testcase {
    public function test_issue_is_idempotent_and_token_is_signed(): void {
        $this->resetAfterTest(true);
        $service = CommercePersonalOfferFactory::create();
        $productid = $this->create_product();
        $request = new CommercePersonalOfferIssueRequest(
            'campaign:legacy-pdf:purchase-42',
            $productid,
            'buyer@example.com',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'trainer-launch',
            null,
            null,
            time() - 10,
            time() + 3600
        );

        $first = $service->issue($request);
        $second = $service->issue($request);
        $this->assertFalse($first->is_replayed());
        $this->assertTrue($second->is_replayed());
        $this->assertSame($first->get_offer()->get_id(), $second->get_offer()->get_id());
        $this->assertSame($first->get_token(), $second->get_token());
        $this->assertSame(CommercePersonalOfferValidationResult::VALID, $service->validate_token($first->get_token())->get_status());

        $tampered = substr($first->get_token(), 0, -1) . ($first->get_token()[-1] === 'a' ? 'b' : 'a');
        $this->assertSame(CommercePersonalOfferValidationResult::INVALID, $service->validate_token($tampered)->get_status());
    }

    public function test_expiration_and_revocation_do_not_consume_on_validation(): void {
        $this->resetAfterTest(true);
        $service = CommercePersonalOfferFactory::create();
        $productid = $this->create_product();
        $now = time();
        $issued = $service->issue(new CommercePersonalOfferIssueRequest(
            'expiry-test', $productid, 'buyer@example.com',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            expiresat: $now + 30
        ));

        $this->assertSame(CommercePersonalOfferValidationResult::VALID, $service->validate_token($issued->get_token(), $now)->get_status());
        $this->assertSame(CommercePersonalOfferValidationResult::VALID, $service->validate_token($issued->get_token(), $now)->get_status());
        $this->assertSame(CommercePersonalOfferValidationResult::EXPIRED, $service->validate_token($issued->get_token(), $now + 31)->get_status());

        $service->revoke($issued->get_offer()->get_offer_uuid(), null, 'test', $now + 5);
        $this->assertSame(CommercePersonalOfferValidationResult::REVOKED, $service->validate_token($issued->get_token(), $now + 6)->get_status());
    }

    public function test_redeem_requires_paid_purchase_and_matching_identity(): void {
        global $DB;
        $this->resetAfterTest(true);
        $service = CommercePersonalOfferFactory::create();
        $productid = $this->create_product();
        $issued = $service->issue(new CommercePersonalOfferIssueRequest(
            'redeem-test', $productid, 'buyer@example.com', CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])
        ));

        $purchaseid = $this->create_purchase('buyer@example.com');
        try {
            $service->redeem($issued->get_token(), $purchaseid);
            $this->fail('Unpaid purchase should not redeem an offer.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('commerce_personal_offer_purchase_not_paid', $exception->errorcode);
        }

        $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'test',
            'providerreference' => 'pay-test',
            'providerorderid' => null,
            'status' => 'paid',
            'currency' => 'EUR',
            'amountminor' => 3000,
            'transactionid' => 'tx-test',
            'legacyrequestid' => null,
            'paidat' => time(),
            'metadatajson' => '{}',
            'paymenturl' => null,
            'providerpayload' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $redeemed = $service->redeem($issued->get_token(), $purchaseid);
        $this->assertSame($purchaseid, $redeemed->get_redeemed_purchase_id());
        $this->assertSame(CommercePersonalOfferValidationResult::REDEEMED, $service->validate_token($issued->get_token())->get_status());
    }

    private function create_product(): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST.K3.' . bin2hex(random_bytes(4)),
            'type' => 'digital', 'status' => 'active', 'name' => 'K3 product', 'description' => '',
            'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    private function create_purchase(string $email): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => 'K3-' . strtoupper(bin2hex(random_bytes(8))),
            'type' => 'digital', 'legacyfamily' => null, 'legacyid' => null, 'userid' => null,
            'customeremail' => $email, 'status' => 'created', 'currency' => 'EUR',
            'subtotalminor' => 3000, 'discountminor' => 0, 'totalminor' => 3000,
            'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}', 'snapshotversion' => 1,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }
}
