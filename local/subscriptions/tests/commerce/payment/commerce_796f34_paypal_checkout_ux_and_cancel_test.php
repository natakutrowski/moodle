<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f34_paypal_checkout_ux_and_cancel_test extends \advanced_testcase {

    public function test_paypal_method_uses_explicit_colour_brand_asset(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString("'paypallogourl' =>", $checkout);
        self::assertStringContainsString('paypal_logo.png', $checkout);
        self::assertStringContainsString('{{paypallogourl}}', $template);
        self::assertStringContainsString('commerce-checkout-payment-method__brands--paypal', $template);
    }


    public function test_paypal_cancel_has_customer_visible_feedback_contract(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('onCancel:', $amd);
        self::assertStringContainsString('config.cancelledMessage', $amd);
        self::assertStringContainsString('cancelFeedbackNode', $amd);
        self::assertStringContainsString('hidePaymentSplash();', $amd);
    }


    public function test_paypal_keeps_safe_fallback_when_sdk_or_popup_cannot_complete(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('payload?.fallbackUrl', $amd);
        self::assertStringContainsString("showPaymentSplash(\n                                'redirect'", $amd);
        self::assertStringContainsString('window.location.assign(', $amd);
    }

}
