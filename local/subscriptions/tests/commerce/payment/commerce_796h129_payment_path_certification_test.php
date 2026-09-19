<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129_payment_path_certification_test extends \advanced_testcase {
    public function test_customer_facing_execution_modes_are_explicit(): void {
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::CARD,
                'stripe'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::PAYPAL,
                'paypal'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::APPLE_PAY,
                'stripe'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::GOOGLE_PAY,
                'stripe'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::LINK,
                'stripe'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::KLARNA,
                'stripe'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::SBP,
                'alfa'
            )
        );
    }

    public function test_uncertified_russian_fast_methods_are_not_claimed_as_embedded(): void {
        self::assertSame(
            CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::ALFA_PAY,
                'alfa'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::SBERPAY,
                'alfa'
            )
        );
        self::assertSame(
            CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::MIR_PAY,
                'alfa'
            )
        );
    }

    public function test_checkout_requires_explicit_customer_payment_choice(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "// H12.7.3: recommendation is presentation advice only.",
            $checkout
        );
        self::assertStringContainsString(
            "? \$requestedmethod\n    : '';",
            $checkout
        );
    }

    public function test_server_action_revalidates_terms_and_payment_route(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            "required_param('paymentmethod'",
            $action
        );
        self::assertStringContainsString(
            "optional_param('accept_terms', 0, PARAM_BOOL)",
            $action
        );
        self::assertStringContainsString(
            "if (!\$acceptterms)",
            $action
        );
        self::assertStringContainsString(
            "->route_for(",
            $action
        );
        self::assertStringContainsString(
            "if (\$paymentroute === null)",
            $action
        );
    }

    public function test_paypal_embedded_has_order_capture_return_and_hosted_fallback_contract(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString("'type' => 'paypal_order'", $action);
        self::assertStringContainsString("'fallbackUrl' =>", $action);
        self::assertStringContainsString("'returnUrl' =>", $action);
        self::assertStringContainsString('payload?.fallbackUrl', $amd);
        self::assertStringContainsString(
            "showPaymentSplash(\n                            'validated'",
            $amd
        );
    }

    public function test_express_wallets_keep_disclosure_open_but_gate_real_payment_click_on_terms(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "express.on(\n            'click'",
            $amd
        );
        self::assertStringContainsString('event.reject();', $amd);
        self::assertStringContainsString('event.resolve();', $amd);
        self::assertStringContainsString(
            "'campusfr:checkout-consent-required'",
            $amd
        );
        self::assertStringNotContainsString(
            'mount.style.pointerEvents =',
            $amd
        );
    }

    public function test_express_wallet_probe_has_visible_loading_and_failure_exit(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            'data-checkout-express-wallet-loading',
            $template
        );
        self::assertStringContainsString('{{expresswalletprobing}}', $template);
        self::assertStringContainsString('window.setTimeout(', $amd);
        self::assertStringContainsString('12000', $amd);
        self::assertStringContainsString("'is-wallet-unavailable'", $amd);
    }

    public function test_currency_specific_card_network_reassurance_is_preserved(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            "\$currency === 'RUB'\n            ? '/local/subscriptions/pix/providers/mir.svg'",
            $checkout
        );
        self::assertStringContainsString(
            "'cardnetworkthirdlabel' => \$currency === 'RUB' ? 'MIR' : 'CB'",
            $checkout
        );
        self::assertStringContainsString(
            'Visa, Mastercard, {{cardnetworkthirdlabel}}',
            $template
        );
    }

    public function test_guest_checkout_identity_and_recovery_guards_remain_in_both_entrypoints(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        foreach ([$checkout, $action] as $source) {
            self::assertStringContainsString(
                'CommerceGuestCheckoutSessionRepository',
                $source
            );
            self::assertStringContainsString(
                'CommerceUnfinishedGuestCheckoutRecoveryService',
                $source
            );
            self::assertStringContainsString(
                'CommerceCheckoutIdentityResolver',
                $source
            );
            self::assertStringContainsString(
                'CommerceGuestCartRecoveryService',
                $source
            );
        }
    }
}
