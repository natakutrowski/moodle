<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_purchase_receipt_k11b_test extends \advanced_testcase {
    public function test_receipt_has_campus_primary_action_and_invoice_attachment(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/mail/template/CommercePurchaseReceiptTemplate.php');
        self::assertIsString($source);
        self::assertStringContainsString('protected function primary_action_url', $source);
        self::assertStringContainsString("'hascampus'", $source);
        self::assertStringContainsString("return 'external';", $source);
        self::assertStringContainsString('CommerceInvoicePdfService', $source);
        self::assertStringContainsString('CommerceMailAttachment', $source);
    }
}
