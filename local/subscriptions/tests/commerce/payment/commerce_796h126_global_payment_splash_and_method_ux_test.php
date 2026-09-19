<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h126_global_payment_splash_and_method_ux_test extends \advanced_testcase {
    public function test_checkout_has_provider_agnostic_splash_contract(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $helper = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_splash.js'
        );

        self::assertStringContainsString(
            'data-checkout-payment-splash',
            $template
        );
        self::assertStringContainsString(
            'data-hosted-splash-methods="{{hostedsplashmethodsjson}}"',
            $template
        );
        self::assertStringContainsString(
            '$hostedsplashmethods',
            $checkout
        );
        self::assertStringContainsString(
            'showPaymentSplash',
            $helper
        );
        self::assertStringContainsString(
            'hidePaymentSplash',
            $helper
        );
    }

    public function test_hosted_redirects_and_stripe_confirmation_share_global_splash(): void {
        global $CFG;

        $submission = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_submission_state.js'
        );
        $inline = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );
        $express = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "showPaymentSplash(\n                    'redirect'",
            $submission
        );
        self::assertStringContainsString(
            "showPaymentSplash(\n                    'processing'",
            $inline
        );
        self::assertStringContainsString(
            "showPaymentSplash('processing');",
            $express
        );
        self::assertStringContainsString(
            'hidePaymentSplash();',
            $inline
        );
        self::assertStringContainsString(
            'hidePaymentSplash();',
            $express
        );
    }

    public function test_sbp_has_local_preparation_feedback(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );

        self::assertStringContainsString(
            'data-checkout-alfa-sbp-loading',
            $template
        );
        self::assertMatchesRegularExpression(
            '/const\s+setPreparingSurface\s*=\s*\n?\s*visible\s*=>\s*\{/',
            $amd
        );
        self::assertMatchesRegularExpression(
            '/setPreparingSurface\(\s*true\s*\);/s',
            $amd
        );
        self::assertMatchesRegularExpression(
            '/setPreparingSurface\(\s*false\s*\);/s',
            $amd
        );
    }

    public function test_alfa_iframe_switch_state_machine_is_preserved(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'const syncPaymentSurface = () => {',
            $amd
        );
        self::assertStringContainsString(
            'const hideAlfaCardSurface = () => {',
            $amd
        );
        self::assertStringContainsString(
            '[data-quick-payment-action]',
            $amd
        );
    }
}
