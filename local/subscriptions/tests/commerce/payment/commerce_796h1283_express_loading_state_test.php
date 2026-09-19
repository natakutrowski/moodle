<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1283_express_loading_state_test extends \advanced_testcase {
    public function test_express_checkout_renders_a_local_probe_loader(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'data-checkout-express-wallet-loading',
            $template
        );
        self::assertStringContainsString(
            '{{expresswalletprobing}}',
            $template
        );
        self::assertStringContainsString(
            'spinner-border spinner-border-sm',
            $template
        );
    }

    public function test_existing_wallet_state_machine_hides_loader_when_resolved(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            "'is-probing',\n        'is-wallet-ready',\n        'is-wallet-unavailable'",
            $amd
        );
        self::assertStringContainsString(
            '.commerce-checkout-express-wallets.is-probing',
            $css
        );
        self::assertStringContainsString(
            '.commerce-checkout-express-wallets.is-wallet-ready',
            $css
        );
        self::assertStringContainsString(
            '.commerce-checkout-express-wallets.is-wallet-unavailable',
            $css
        );
    }

    public function test_probe_loader_has_a_fail_safe_and_graceful_stripe_failure(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            'window.setTimeout(',
            $amd
        );
        self::assertStringContainsString(
            '12000',
            $amd
        );
        self::assertStringContainsString(
            "section.dataset.walletState =\n            'unavailable';",
            $amd
        );
    }

    public function test_express_consent_contract_is_not_regressed(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "express.on(\n            'click'",
            $amd
        );
        self::assertStringContainsString(
            'event.reject();',
            $amd
        );
        self::assertStringContainsString(
            'event.resolve();',
            $amd
        );
        self::assertStringNotContainsString(
            'mount.style.pointerEvents =',
            $amd
        );
    }

    public function test_paypal_h1282_hardening_is_preserved_in_current_tree(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            'let starting = false;',
            $amd
        );
        self::assertStringContainsString(
            "showPaymentSplash(\n                            'validated'",
            $amd
        );
        self::assertStringContainsString(
            'payload?.fallbackUrl',
            $amd
        );
    }
}
