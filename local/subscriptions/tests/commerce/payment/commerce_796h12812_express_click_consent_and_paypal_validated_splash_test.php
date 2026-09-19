<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12812_express_click_consent_and_paypal_validated_splash_test extends \advanced_testcase {
    public function test_express_payment_button_click_is_rejected_before_consent(): void {
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
            "'campusfr:checkout-consent-required'",
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

    public function test_paypal_approval_uses_validated_splash_state(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "showPaymentSplash(\n                            'validated'",
            $amd
        );
    }

    public function test_shared_splash_has_localized_validated_copy(): void {
        global $CFG;

        $splash = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_splash.js'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "resolvedState === 'validated'",
            $splash
        );
        self::assertStringContainsString(
            'data-validated-title="{{paymentvalidatedlabel}}"',
            $template
        );
        self::assertStringContainsString(
            'data-validated-message="{{paymentvalidatedmessage}}"',
            $template
        );
        self::assertStringContainsString(
            "'paymentvalidatedlabel' => get_string(",
            $checkout
        );
        self::assertStringContainsString(
            "'paymentvalidatedmessage' => get_string(",
            $checkout
        );
    }
}
