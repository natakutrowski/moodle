<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1281_paypal_embedded_v6_test extends \advanced_testcase {
    public function test_paypal_route_is_now_campus_embedded_with_hosted_fallback(): void {
        global $CFG;

        $policy = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/execution/'
            . 'CommerceCheckoutExecutionPolicy.php'
        );

        self::assertStringContainsString(
            "\$provider === 'paypal'",
            $policy
        );
        self::assertStringContainsString(
            'CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED',
            $policy
        );
        self::assertStringContainsString(
            'CommercePaymentMethod::PAYPAL,',
            $policy
        );
    }

    public function test_checkout_exposes_browser_safe_paypal_v6_configuration(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $configuration = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalGatewayConfiguration.php'
        );

        self::assertStringContainsString(
            'get_web_sdk_url',
            $configuration
        );
        self::assertStringContainsString(
            'https://www.sandbox.paypal.com/web-sdk/v6/core',
            $configuration
        );
        self::assertStringContainsString(
            "'haspaypalcandidate' =>",
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
    }

    public function test_ajax_checkout_returns_paypal_order_id_and_hosted_fallback(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            "'type' => 'paypal_order'",
            $action
        );
        self::assertStringContainsString(
            "'orderId' => \$orderid",
            $action
        );
        self::assertStringContainsString(
            "'fallbackUrl' =>",
            $action
        );
        self::assertStringContainsString(
            "'returnUrl' =>",
            $action
        );
        self::assertStringContainsString(
            'find_by_provider_reference',
            $action
        );
    }

    public function test_paypal_amd_uses_v6_session_and_auto_presentation(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            'paypal.createInstance({',
            $amd
        );
        self::assertStringContainsString(
            "'paypal-payments'",
            $amd
        );
        self::assertStringContainsString(
            'findEligibleMethods({',
            $amd
        );
        self::assertStringContainsString(
            'createPayPalOneTimePaymentSession({',
            $amd
        );
        self::assertStringContainsString(
            "presentationMode:\n                        'auto'",
            $amd
        );
        self::assertStringContainsString(
            "payload.fallbackUrl",
            $amd
        );
    }

    public function test_approval_returns_to_existing_server_side_capture_flow(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        $return = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/return.php'
        );

        self::assertStringContainsString(
            "url.searchParams.set(\n        'orderId'",
            $amd
        );
        self::assertStringContainsString(
            'PayPalReturnCaptureService::create($DB)',
            $return
        );
    }
}
