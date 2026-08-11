<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\certification\CommercePersonalOfferCertificationService;

/**
 * @covers \local_subscriptions\commerce\personaloffer\certification\CommercePersonalOfferCertificationService
 */
final class commerce_personal_offer_certification_test extends advanced_testcase {
    public function test_empty_database_is_certified(): void {
        global $DB;
        $this->resetAfterTest(true);
        $result = CommercePersonalOfferCertificationService::create($DB)->certify();
        $this->assertTrue($result['certified']);
        $this->assertSame(0, $result['errors']);
    }

    public function test_offer_without_token_is_not_certified(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'K7-TARGET', 'type' => 'digital', 'status' => 'active',
            'name' => 'K7 target', 'description' => null, 'metadatajson' => '{}',
            'availablefrom' => null, 'availableuntil' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_offer', (object)[
            'offeruuid' => md5('k7-no-token'), 'campaignkey' => 'k7-cert',
            'sourcepurchaseid' => null, 'targetproductid' => $productid,
            'beneficiaryuserid' => null, 'beneficiaryemail' => 'k7@example.test',
            'status' => 'issued', 'validfrom' => null, 'expiresat' => null,
            'termsversion' => 1, 'termsjson' => '{"type":"percentage_discount","basispoints":1000}',
            'metadatajson' => '{}', 'redeemedat' => null, 'redeemedpurchaseid' => null,
            'revokedat' => null, 'revokedbyuserid' => null, 'revokereason' => null,
            'issuedbyuserid' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $result = CommercePersonalOfferCertificationService::create($DB)->certify('k7-cert');
        $this->assertFalse($result['certified']);
        $checks = array_column($result['checks'], null, 'key');
        $this->assertSame(1, $checks['missing_token']['count']);
    }

    public function test_email_change_is_warning_and_strict_failure(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'current@example.test']);
        $now = time();
        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'K7-TARGET-2', 'type' => 'digital', 'status' => 'active',
            'name' => 'K7 target 2', 'description' => null, 'metadatajson' => '{}',
            'availablefrom' => null, 'availableuntil' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $offerid = $DB->insert_record('local_subs_commerce_offer', (object)[
            'offeruuid' => md5('k7-email-change'), 'campaignkey' => 'k7-warning',
            'sourcepurchaseid' => null, 'targetproductid' => $productid,
            'beneficiaryuserid' => $user->id, 'beneficiaryemail' => 'historic@example.test',
            'status' => 'issued', 'validfrom' => null, 'expiresat' => null,
            'termsversion' => 1, 'termsjson' => '{"type":"percentage_discount","basispoints":1000}',
            'metadatajson' => '{}', 'redeemedat' => null, 'redeemedpurchaseid' => null,
            'revokedat' => null, 'revokedbyuserid' => null, 'revokereason' => null,
            'issuedbyuserid' => null, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_offer_token', (object)[
            'offerid' => $offerid, 'tokenversion' => 1, 'tokenhash' => hash('sha256', 'k7-token'),
            'issuancekey' => 'k7-warning-key', 'requesthash' => hash('sha256', 'k7-request'), 'timecreated' => $now,
        ]);
        $normal = CommercePersonalOfferCertificationService::create($DB)->certify('k7-warning', false);
        $strict = CommercePersonalOfferCertificationService::create($DB)->certify('k7-warning', true);
        $this->assertTrue($normal['certified']);
        $this->assertFalse($strict['certified']);
        $this->assertGreaterThan(0, $strict['warnings']);
    }
}
