<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e11_payment_architecture_cli_test extends advanced_testcase {
    public function test_generic_architecture_audit_is_read_only_and_future_provider_friendly(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_commerce_payment_architecture.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentArchitectureCertificationService',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentArchitectureInspector',
            $contents
        );
        $this->assertStringContainsString(
            'Architecture certification: PASS',
            $contents
        );

        $this->assertStringNotContainsString(
            'set_config(',
            $contents
        );
        $this->assertStringNotContainsString(
            'StripeCommercePaymentProvider::KEY',
            $contents
        );
        $this->assertStringNotContainsString(
            'AlfaCommercePaymentProvider::KEY',
            $contents
        );
    }

    public function test_architecture_audit_remains_read_only_without_pinning_current_plugin_version(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/audit_commerce_payment_architecture.php'
        );
        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        $this->assertIsString($contents);
        $this->assertIsString($version);
        $this->assertStringNotContainsString('set_config(', $contents);
        preg_match('/\$plugin->version\s*=\s*(\d+);/', $version, $matches);
        $this->assertNotEmpty($matches);
        $this->assertGreaterThanOrEqual(2026082301, (int)$matches[1]);
    }
}
