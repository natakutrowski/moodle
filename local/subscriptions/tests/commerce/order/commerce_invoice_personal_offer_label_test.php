<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_invoice_personal_offer_label_test extends advanced_testcase {
    public function test_invoice_pdf_uses_personal_offer_label_for_offer_discounts(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/order/invoice/CommerceInvoicePdfService.php'
        );

        $this->assertStringContainsString(
            "=== 'personaloffer'",
            $service
        );
        $this->assertStringContainsString(
            "commerce_personal_offer_order_discount_label",
            $service
        );
        $this->assertStringContainsString(
            "commerce_invoice_other_discount",
            $service
        );
    }
}
