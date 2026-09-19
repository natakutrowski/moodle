<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129_paypal_embedded_activation_chain_test extends \advanced_testcase {
    public function test_checkout_really_loads_paypal_embedded_runtime(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'PayPalGatewayConfiguration',
            $checkout
        );
        self::assertStringContainsString(
            '$haspaypalcandidate = count(',
            $checkout
        );
        self::assertStringContainsString(
            "'local_subscriptions/checkout_paypal_embedded'",
            $checkout
        );
        self::assertStringContainsString(
            'data-checkout-paypal-embedded',
            $template
        );
        self::assertStringContainsString(
            'data-client-id="{{paypalclientid}}"',
            $template
        );
        self::assertStringContainsString(
            'data-action-url="{{paypalactionurl}}"',
            $template
        );
    }

    public function test_known_good_h1282_paypal_flow_is_restored(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "presentationMode:\n                        'auto'",
            $amd
        );
        self::assertStringContainsString(
            'let starting = false;',
            $amd
        );
        self::assertStringContainsString(
            "showPaymentSplash(\n                'preparing'",
            $amd
        );
        self::assertStringContainsString(
            "showPaymentSplash(\n                            'validated'",
            $amd
        );
    }

    public function test_h1284_intent_change_experiment_is_removed(): void {
        global $CFG;

        $intent = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringNotContainsString(
            "'campusfr:payment-intent-change'",
            $intent
        );
    }

    public function test_later_rub_mir_reassurance_is_preserved(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            '/pix/providers/mir.svg',
            $checkout
        );
        self::assertStringContainsString(
            "'cardnetworkthirdlabel' => \$currency === 'RUB' ? 'MIR' : 'CB'",
            $checkout
        );
    }
}
