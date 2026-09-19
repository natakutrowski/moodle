<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1132_sbp_execution_test extends \advanced_testcase {

    public function test_native_alfa_gateway_exposes_dynamic_sbp_qr_contract(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString("'/payment/rest/sbp/c2b/qr/dynamic/get.do'", $source);
        self::assertStringContainsString("'mdOrder' => \$orderid", $source);
        self::assertStringContainsString("'qrFormat' => 'image'", $source);
        self::assertStringContainsString("'api_userpass'", $source);
        self::assertStringContainsString("\$response['renderedQr']", $source);
        self::assertStringContainsString("\$response['payload']", $source);
        self::assertStringContainsString("\$response['qrId']", $source);
    }


    public function test_sbp_is_executable_as_alfa_embedded_method(): void {
        self::assertSame(
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_route('sbp', 'alfa')
        );
        self::assertTrue(\local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now('sbp'));
    }


    public function test_sbp_checkout_surface_uses_ajax_embedded_response_and_brand_assets(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        self::assertIsString($action);
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
        global $CFG;

        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );
        self::assertIsString($javascript);

        self::assertStringContainsString("'type' => 'alfa_sbp'", $action);
        self::assertStringContainsString('checkout_alfa_sbp', $checkout);
        self::assertStringContainsString('data-checkout-alfa-sbp-qr', $template);
        self::assertMatchesRegularExpression("/body\\.set\\(\\s*'paymentmethod'\\s*,\\s*'sbp'\\s*\\)/s", $javascript);
        self::assertStringContainsString("payload.type !== 'alfa_sbp'", $javascript);
        self::assertStringContainsString('sbp_logo.png', $checkout);
    }

}
