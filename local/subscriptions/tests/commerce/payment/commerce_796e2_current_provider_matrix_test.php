<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e2_current_provider_matrix_test extends \advanced_testcase {

    public function test_current_provider_matrix_contains_certified_stripe_and_alfa_methods(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php'
        );
        self::assertIsString($stripe);
        global $CFG;

        $alfa = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        self::assertIsString($alfa);

        foreach (['CARD', 'APPLE_PAY', 'GOOGLE_PAY', 'LINK', 'KLARNA'] as $method) {
            self::assertStringContainsString('CommercePaymentMethod::' . $method, $stripe);
        }
        foreach (['CARD', 'ALFA_PAY', 'SBP'] as $method) {
            self::assertStringContainsString('CommercePaymentMethod::' . $method, $alfa);
        }
    }


    public function test_checkout_uses_availability_and_route_execution_boundaries(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString('CommercePaymentAvailabilityResolver', $checkout);
        self::assertStringContainsString('CommerceCheckoutPaymentOrchestrator', $checkout);
        self::assertStringContainsString('CommerceCheckoutExecutionPolicy::is_executable_now', $checkout);
        self::assertStringContainsString('$expressroutes', $checkout);
        self::assertStringContainsString('$inlineroutes', $checkout);
        self::assertStringNotContainsString("apple_pay_eligibility", $checkout);
        self::assertStringNotContainsString("google_pay_eligibility", $checkout);
    }

}
