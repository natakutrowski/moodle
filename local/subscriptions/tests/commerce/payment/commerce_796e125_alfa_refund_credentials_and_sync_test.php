<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e125_alfa_refund_credentials_and_sync_test extends advanced_testcase {
    public function test_alfa_refund_credentials_can_be_dedicated_with_standard_fallback(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $this->assertStringContainsString(
            'alfa_{$env}_refund_username',
            $gateway
        );
        $this->assertStringContainsString(
            "'refund_username' => \$run !== '' ? \$run : (\$un ?: null)",
            $gateway
        );
        $this->assertStringContainsString(
            'alfa_refund_access_denied',
            $gateway
        );
    }

    public function test_purchase_view_sync_is_provider_capability_driven(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            '$refundhistoryproviders',
            $contents
        );
        $this->assertStringNotContainsString(
            '$payment->provider === Provider::STRIPE',
            $contents
        );
    }

    public function test_alfa_history_falls_back_to_refunded_amount(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $this->assertStringContainsString(
            "'refundedAmount'",
            $gateway
        );
        $this->assertStringContainsString(
            "'aggregate' => true",
            $gateway
        );
        $this->assertStringContainsString(
            "'alfa-order-refunded-' . \$providerpaymentid",
            $gateway
        );
    }
}
