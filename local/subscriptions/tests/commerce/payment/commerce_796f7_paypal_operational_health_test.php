<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f7_paypal_operational_health_test extends advanced_testcase {
    public function test_operational_health_is_read_only(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalOperationalHealthService.php'
        );

        foreach ([
            'is_configured()',
            'get_webhook_id()',
            'test_connection()',
            'new PayPalOperationalHealth(',
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }

        $healthdto = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalOperationalHealth.php'
        );

        $this->assertStringContainsString(
            'readyforcheckout',
            $healthdto
        );
        $this->assertStringContainsString(
            'readyforwebhooks',
            $healthdto
        );

        $this->assertStringNotContainsString('create_order(', $contents);
        $this->assertStringNotContainsString('capture_order(', $contents);
        $this->assertStringNotContainsString('refund_capture(', $contents);
    }

    public function test_admin_page_never_displays_paypal_secrets(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/provider.php'
        );

        $this->assertStringNotContainsString('get_client_secret(', $contents);
        $this->assertStringNotContainsString('get_client_id(', $contents);
        $this->assertStringContainsString(
            'Provider::PAYPAL',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentProviderOperationalStatusService',
            $contents
        );
    }

    public function test_cli_has_remote_health_check(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_paypal_operational_health.php'
        );

        $this->assertStringContainsString('inspect(true)', $contents);
        $this->assertStringContainsString(
            'PayPal operational health: PASS',
            $contents
        );
    }
}
