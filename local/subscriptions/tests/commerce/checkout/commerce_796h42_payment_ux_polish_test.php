<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h42_payment_ux_polish_test extends \advanced_testcase {

    public function test_express_divider_is_rendered_only_with_express_candidate(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        $section = strpos($template, '{{#hasexpresswalletcandidate}}');
        $divider = strpos($template, 'commerce-checkout-express-wallets__divider', $section);
        $end = strpos($template, '{{/hasexpresswalletcandidate}}', $section);
        self::assertNotFalse($section);
        self::assertNotFalse($divider);
        self::assertNotFalse($end);
        self::assertGreaterThan($section, $divider);
        self::assertGreaterThan($divider, $end);
    }


    public function test_link_and_klarna_remain_admin_policy_gated(): void {
        $this->resetAfterTest();

        set_config(
            'commerce_presented_payment_methods',
            'card,link',
            'local_subscriptions'
        );

        $policy =
            new \local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy();

        self::assertTrue($policy->is_method_allowed('link'));
        self::assertFalse($policy->is_method_allowed('klarna'));

        set_config(
            'commerce_presented_payment_methods',
            'card,klarna',
            'local_subscriptions'
        );

        $policy =
            new \local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy();

        self::assertFalse($policy->is_method_allowed('link'));
        self::assertTrue($policy->is_method_allowed('klarna'));
    }

    public function test_link_and_klarna_are_server_side_express_candidates(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $stripe = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );

        self::assertIsString($checkout);
        self::assertIsString($stripe);

        self::assertStringContainsString(
            '$expresspaymentmethods',
            $checkout
        );
        self::assertStringContainsString(
            '$expresswalletmethods =',
            $checkout
        );
        self::assertStringContainsString(
            'CommercePaymentMethod::LINK',
            $stripe
        );
        self::assertStringContainsString(
            'CommercePaymentMethod::KLARNA',
            $stripe
        );
    }

    public function test_express_checkout_presentation_is_minimal(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('data-checkout-express-wallet-element', $template);
        self::assertStringContainsString('data-checkout-express-wallet-loading', $template);
        self::assertStringContainsString('commerce-checkout-express-wallets__divider', $template);
    }

}
