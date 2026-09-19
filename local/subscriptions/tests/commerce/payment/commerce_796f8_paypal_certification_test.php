<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f8_paypal_certification_test extends advanced_testcase {
    public function test_certification_is_contract_based_and_side_effect_free(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalIntegrationCertificationService.php'
        );

        foreach ([
            'CommercePaymentMethod::PAYPAL',
            'supports_redirect()',
            'supports_retrieval()',
            'supports_refunds()',
            'CommerceRefundCapablePaymentProvider',
            'CommerceRefundHistoryCapablePaymentProvider',
            'get_currencies()',
            'PayPalOperationalHealthService',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }

        $this->assertStringNotContainsString(
            'create_order(',
            $contents
        );
        $this->assertStringNotContainsString(
            'capture_order(',
            $contents
        );
        $this->assertStringNotContainsString(
            'refund_capture(',
            $contents
        );
    }

    public function test_final_cli_has_optional_remote_and_strict_modes(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_paypal_integration.php'
        );

        $this->assertStringContainsString(
            "'remote' => false",
            $contents
        );
        $this->assertStringContainsString(
            "'strict' => false",
            $contents
        );
        $this->assertStringContainsString(
            'PayPal certification: PASS',
            $contents
        );
        $this->assertStringContainsString(
            'It never creates, captures or refunds a payment.',
            $contents
        );
    }
}
