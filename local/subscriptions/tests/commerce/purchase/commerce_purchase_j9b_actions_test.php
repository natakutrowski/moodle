<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/**
 * Certifies the J9B CRM purchase action surface.
 */
final class commerce_purchase_j9b_actions_test extends advanced_testcase {
    public function test_purchase_page_exposes_safe_customer_and_operational_actions(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents($root . '/admin/commerce/purchases/view.php');
        $endpoint = file_get_contents(
            $root . '/admin/commerce/purchases/resend_receipt.php'
        );

        self::assertIsString($view);
        self::assertIsString($endpoint);
        self::assertStringContainsString(
            "'/local/subscriptions/order_invoice.php'",
            $view
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/mail/index.php'",
            $view
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_receipt.php'",
            $view
        );
        self::assertStringContainsString("'data-confirmation' => 'modal'", $view);
        self::assertStringContainsString('require_sesskey()', $endpoint);
        self::assertStringContainsString('Capabilities::MANAGE_SUBSCRIPTIONS', $endpoint);
    }

    public function test_manual_receipt_resend_uses_a_new_outbox_intention(): void {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents(
            $root . '/classes/commerce/purchase/action/'
                . 'CommercePurchaseCommunicationActionService.php'
        );

        self::assertIsString($service);
        self::assertStringContainsString(
            'CommerceMailType::PURCHASE_RECEIPT',
            $service
        );
        self::assertStringContainsString("':manual:'", $service);
        self::assertStringContainsString('CommerceMailRuntime::queue_service()', $service);
        self::assertStringContainsString('CommerceMailRuntime::processor()', $service);
        self::assertStringContainsString("'manualresend'", $service);
    }
}
