<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h414_wallet_visibility_diagnostics_test extends advanced_testcase {
    public function test_allowed_wallets_are_requested_from_admin_authorized_config(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            "allowed(config, 'apple_pay')",
            "allowed(config, 'google_pay')",
            "allowed(config, 'link')",
            "allowed(config, 'klarna')",
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }

    public function test_production_wallet_module_has_no_browser_diagnostic_logging(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach (['console.info', 'console.log', 'navigator.userAgent', 'serverDiagnostics'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $js);
        }
    }
}
