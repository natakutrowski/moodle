<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h121_zero_friction_payment_hierarchy_test extends advanced_testcase {
    public function test_non_express_methods_are_never_hidden_behind_disclosure(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            '$primary = array_values(',
            $presenter
        );
        self::assertStringContainsString(
            '$quick = array_values(',
            $presenter
        );
        self::assertStringContainsString(
            '$others = [];',
            $presenter
        );
        self::assertStringNotContainsString(
            'commerce-checkout-other-methods',
            $template
        );
        self::assertStringNotContainsString(
            '{{otherpaymentmethodslabel}}',
            $template
        );
    }

    public function test_paypal_remains_a_first_class_visible_method(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            "'ispaypal' => \$method === CommercePaymentMethod::PAYPAL",
            $presenter
        );
        self::assertStringContainsString(
            '{{#ispaypal}}',
            $template
        );
        self::assertStringContainsString(
            '{{paypallogourl}}',
            $template
        );
    }

    public function test_existing_express_surface_is_preserved(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            '$route->is_express()',
            $checkout
        );
        self::assertStringContainsString(
            'data-checkout-express-wallet-section',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-express-wallet-element',
            $template
        );
    }
}
