<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutService;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_campaign_email_m3h7_test extends \advanced_testcase {
    public function test_test_offer_can_be_opened_while_authenticated_as_unrelated_admin(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = $this->create_product('M3H7.TEST');
        $tester = $this->getDataGenerator()->create_user([
            'email' => 'admin@example.test',
            'firstname' => 'Admin',
            'lastname' => 'Tester',
        ]);
        $this->setUser($tester);

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3h7-test-' . bin2hex(random_bytes(4)),
            $productid,
            'recipient@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm3h7-campaign',
            null,
            null,
            time() - 10,
            time() + HOURSECS,
            ['campaignemailtest' => true],
            (int)$tester->id
        ));

        $result = CommercePersonalOfferCheckoutService::create($DB)->validate_entry(
            $issued->get_token(),
            'EUR',
            (int)$tester->id,
            (string)$tester->email
        );

        $this->assertSame($issued->get_offer()->get_offer_uuid(), $result['offer']->get_offer_uuid());
        $this->assertSame('M3H7.TEST', $result['sku']);
    }

    public function test_real_offer_still_rejects_unrelated_authenticated_identity(): void {
        global $DB;

        $this->resetAfterTest(true);

        $productid = $this->create_product('M3H7.REAL');
        $tester = $this->getDataGenerator()->create_user([
            'email' => 'admin@example.test',
            'firstname' => 'Admin',
            'lastname' => 'Tester',
        ]);
        $this->setUser($tester);

        $issued = CommercePersonalOfferFactory::create($DB)->issue(new CommercePersonalOfferIssueRequest(
            'm3h7-real-' . bin2hex(random_bytes(4)),
            $productid,
            'recipient@example.test',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'm3h7-campaign',
            null,
            null,
            time() - 10,
            time() + HOURSECS
        ));

        $this->expectException(\moodle_exception::class);
        CommercePersonalOfferCheckoutService::create($DB)->validate_entry(
            $issued->get_token(),
            'EUR',
            (int)$tester->id,
            (string)$tester->email
        );
    }

    private function create_product(string $sku): int {
        global $DB;

        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'digital',
            'status' => 'active',
            'name' => $sku,
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_prod_price', (object)[
            'productid' => $productid,
            'currency' => 'EUR',
            'amountminor' => 5500,
            'provider' => null,
            'providerpriceid' => null,
            'active' => 1,
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return $productid;
    }
}
