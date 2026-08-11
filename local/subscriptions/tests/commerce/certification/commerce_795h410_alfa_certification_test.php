<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\certification\payment\CommerceAlfaPurchaseCertifier;

/** @covers \local_subscriptions\commerce\certification\payment\CommerceAlfaPurchaseCertifier */
final class commerce_795h410_alfa_certification_test extends advanced_testcase {
    public function test_missing_purchase_is_not_certified(): void {
        global $DB;
        $this->resetAfterTest();
        $report = (new CommerceAlfaPurchaseCertifier($DB))->certify('cmp_missing_h410');
        $this->assertFalse($report['certified']);
        $this->assertSame('FAIL', $report['checks'][0]['status']);
    }

    public function test_purchase_certifier_uses_native_reference_columns(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/certification/payment/CommerceAlfaPurchaseCertifier.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("['purchasereference' => (string) \$purchase->reference]", $source);
        $this->assertStringContainsString("['grantreference' => (string) \$grant->grantreference]", $source);
        $this->assertStringNotContainsString("['grantid' => \$grant->id]", $source);
    }

    public function test_alfa_certification_files_exist(): void {
        $root = __DIR__ . '/../../../';
        $this->assertFileExists($root . 'cli/commerce/certification/certify_alfa.php');
        $this->assertFileExists($root . 'cli/commerce/certification/certify_payment_provider.php');
        $this->assertFileExists($root . 'webhook/alfa.php');
        $this->assertFileExists($root . 'payment/return.php');
    }
}
