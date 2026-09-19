<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h10_alfa_widget_surface_test extends advanced_testcase {
    public function test_widget_configuration_is_environment_specific(): void {
        $this->resetAfterTest();

        set_config('alfa_env', 'test', 'local_subscriptions');
        set_config('alfa_widget_enabled', 1, 'local_subscriptions');
        set_config(
            'alfa_test_widget_token',
            'test-public-token',
            'local_subscriptions'
        );

        $this->assertTrue(
            AlfaWidgetConfiguration::is_available()
        );
        $this->assertSame(
            'test-public-token',
            AlfaWidgetConfiguration::token()
        );
        $this->assertSame(
            'https://testpay.alfabank.ru/assets/alfa-payment.js',
            AlfaWidgetConfiguration::script_url()
        );
        $this->assertSame(
            'test',
            AlfaWidgetConfiguration::gateway()
        );
    }

    public function test_alfa_card_becomes_embedded_only_when_widget_is_available(): void {
        $this->resetAfterTest();

        set_config('alfa_env', 'test', 'local_subscriptions');
        set_config('alfa_widget_enabled', 1, 'local_subscriptions');
        set_config(
            'alfa_test_widget_token',
            'test-public-token',
            'local_subscriptions'
        );

        $this->assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::CARD,
                'alfa'
            )
        );

        set_config('alfa_widget_enabled', 0, 'local_subscriptions');

        $this->assertSame(
            CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
            CommerceCheckoutExecutionPolicy::mode_for_route(
                CommercePaymentMethod::CARD,
                'alfa'
            )
        );
    }

    public function test_checkout_has_dedicated_alfa_widget_executor(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            "'local_subscriptions/checkout_alfa_widget'",
            $checkout
        );
        $this->assertStringContainsString(
            'data-checkout-alfa-widget',
            $template
        );
        $this->assertStringContainsString(
            'data-checkout-alfa-widget-mount',
            $template
        );
    }

    public function test_stripe_inline_methods_are_filtered_by_provider(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "\$route->get_provider() === 'stripe'",
            $checkout
        );
    }
}
