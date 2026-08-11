<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\dto\CommercePersonalOfferIssueRequest;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

final class commerce_personal_offer_admin_test extends advanced_testcase {
    public function test_secure_url_and_reissue_successor(): void {
        $this->resetAfterTest(true);
        $productid = $this->create_product();
        $service = CommercePersonalOfferFactory::create();
        $issued = $service->issue(new CommercePersonalOfferIssueRequest(
            'k6-expired', $productid, 'buyer@example.com',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]),
            'k6-campaign', expiresat: time() - 10
        ))->get_offer();

        $admin = new CommercePersonalOfferAdminService($GLOBALS['DB']);
        $this->assertNotNull($admin->secure_url($issued));
        $successor = $admin->reissue($issued, 2, 30);
        $this->assertNotSame($issued->get_id(), $successor->get_id());
        $this->assertSame($issued->get_offer_uuid(), $successor->get_metadata()['reissuedfromofferuuid']);
        $this->assertSame(CommercePersonalOffer::STATUS_ISSUED, $successor->get_status());
        $this->assertTrue($successor->is_available_at(time()));
    }

    public function test_revoke_is_audited_and_stats_follow_lifecycle(): void {
        $this->resetAfterTest(true);
        $productid = $this->create_product();
        $service = CommercePersonalOfferFactory::create();
        $offer = $service->issue(new CommercePersonalOfferIssueRequest(
            'k6-revoke', $productid, 'buyer@example.com',
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000]), 'k6-campaign'
        ))->get_offer();
        $admin = new CommercePersonalOfferAdminService($GLOBALS['DB']);
        $revoked = $admin->revoke($offer, 2, 'operator test');
        $this->assertSame(CommercePersonalOffer::STATUS_REVOKED, $revoked->get_status());
        $this->assertSame('operator test', $revoked->get_revoke_reason());
        $stats = $admin->global_stats();
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['revoked']);
    }

    private function create_product(): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'TEST.K6.' . bin2hex(random_bytes(4)),
            'type' => 'digital', 'status' => 'active', 'name' => 'K6 product', 'description' => '',
            'metadatajson' => '{}', 'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }
}
