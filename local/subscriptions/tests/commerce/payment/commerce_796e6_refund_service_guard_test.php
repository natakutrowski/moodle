<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e6_refund_service_guard_test extends advanced_testcase {
    public function test_refund_service_requires_both_capability_and_contract(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundService.php'
        );

        $this->assertStringContainsString(
            'supports_refunds()',
            $contents
        );
        $this->assertStringContainsString(
            'instanceof CommerceRefundCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            "'refund_not_supported'",
            $contents
        );
    }

    public function test_payment_diagnostics_use_provider_icons_and_translated_capability_labels(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );

        $this->assertStringContainsString(
            '/pix/providers/',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_payment_capability_redirect',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_payment_capability_refund',
            $contents
        );
        $this->assertStringNotContainsString(
            "'refunds' => 'Refund'",
            $contents
        );
    }
}
