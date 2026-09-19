<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e3_checkout_payment_method_ux_test extends \advanced_testcase {

    public function test_checkout_uses_payment_method_as_customer_input_not_provider(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($page);
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        self::assertIsString($action);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString("optional_param('paymentmethod'", $page);
        self::assertStringContainsString("required_param('paymentmethod'", $action);
        self::assertStringContainsString('name="paymentmethod"', $template);
        self::assertStringContainsString('data-payment-action-card', $template);
        self::assertStringNotContainsString('name="provider"', $template);
    }


    public function test_checkout_action_resolves_provider_server_side_from_payment_route(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        self::assertIsString($action);

        self::assertStringContainsString('CommercePaymentAvailabilityResolver', $action);
        self::assertStringContainsString('CommerceCheckoutPaymentOrchestrator', $action);
        self::assertStringContainsString('->route_for(', $action);
        self::assertStringContainsString('$provider = $paymentroute->get_provider();', $action);
    }


    public function test_presenter_does_not_expose_provider_as_customer_form_input(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringNotContainsString('name="provider"', $template);
        self::assertStringContainsString('{{#primaryactioncards}}', $template);
        self::assertStringContainsString('{{#secondaryactioncards}}', $template);
    }

}
