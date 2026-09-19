<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h426_wallet_diagnostics_only_cleanup_test extends advanced_testcase {
    public function test_debug_instrumentation_is_removed_but_wallet_contract_is_preserved(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach (['console.info', 'data-wallet-debug-log', 'serverDiagnostics'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $js);
        }
        foreach ([
            "'availablepaymentmethodschange'",
            'expressPaymentMethodTypes(',
            'express.mount(mount);',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }

    public function test_debug_ui_is_removed_without_removing_wallet_mount_hook(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        $this->assertStringNotContainsString('data-wallet-debug-status', $template);
        $this->assertStringNotContainsString('data-wallet-debug-log', $template);
        $this->assertStringContainsString('data-checkout-express-wallet-element', $template);
    }
}
