<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\digital\CommerceDigitalPurchaseCertifier;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;

final class commerce_795h48_digital_purchase_certifier_test extends advanced_testcase {
    public function test_missing_purchase_is_not_certified(): void {
        global $DB;
        $this->resetAfterTest(true);

        $report = (new CommerceDigitalPurchaseCertifier($DB))->certify('cmp_missing');

        $this->assertFalse($report->certified);
        $this->assertSame('FAIL', $report->checks[0]['status']);
        $this->assertSame('purchase', $report->checks[0]['key']);
    }

    public function test_native_download_resolver_finds_a_real_file(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        $now = time();
        $sku = 'DIGITAL.H48.TEST';
        $filename = 'h48-test.pdf';
        $directory = $CFG->dataroot . '/local_subscriptions/private_pdfs';
        check_dir_exists($directory, true, true);
        file_put_contents($directory . '/' . $filename, '%PDF-1.4 H4.8 test');

        $DB->insert_record('local_subs_commerce_product', (object) [
            'sku' => $sku,
            'type' => 'digital_download',
            'status' => 'active',
            'name' => 'H4.8 digital test',
            'description' => '',
            'metadatajson' => json_encode(['filename' => $filename]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $token = str_repeat('a', 64);
        $DB->insert_record('local_subs_commerce_dig_access', (object) [
            'grantreference' => 'ent-h48-test',
            'idempotencykey' => 'h48:test',
            'purchasereference' => 'cmp-h48-test',
            'productsku' => $sku,
            'resourcekey' => 'digital-product:999999',
            'beneficiaryuserid' => null,
            'beneficiaryemail' => 'h48-test@campusfr.test',
            'downloadtoken' => $token,
            'maxdownloads' => 2,
            'downloadcount' => 0,
            'validfrom' => $now - 10,
            'validuntil' => $now + 3600,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $resolved = (new CommerceNativeDigitalDownloadResolver($DB))->resolve($token, $now);

        $this->assertSame($filename, $resolved['filename']);
        $this->assertFileIsReadable($resolved['filepath']);
    }
}
