<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e10_refund_context_and_sync_test extends advanced_testcase {
    public function test_sales_context_menu_exposes_refund_only_for_refundable_stripe_payment(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/index.php'
        );

        $this->assertStringContainsString(
            'local_subscriptions_commerce_refund',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_refund_action',
            $contents
        );
        $this->assertStringContainsString(
            'refund.php',
            $contents
        );
    }

    public function test_refund_sync_is_import_only(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundImportService.php'
        );

        $this->assertStringContainsString(
            'list_refunds(',
            $service
        );
        $this->assertStringContainsString(
            'import_provider_refund(',
            $service
        );
        $this->assertStringNotContainsString(
            '->refund(',
            $service
        );
    }

    public function test_stripe_history_uses_refund_list_api_not_create(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/stripe/StripeGateway.php'
        );

        $this->assertStringContainsString(
            '\\Stripe\\Refund::all(',
            $gateway
        );
    }
}
