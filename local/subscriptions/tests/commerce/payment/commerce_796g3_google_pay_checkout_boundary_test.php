<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g3_google_pay_checkout_boundary_test extends \advanced_testcase {

    public function test_google_pay_is_now_an_embedded_executable_method(): void {
        self::assertSame(
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method(
                \local_subscriptions\commerce\payment\method\CommercePaymentMethod::GOOGLE_PAY
            )
        );
        self::assertTrue(
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now(
                \local_subscriptions\commerce\payment\method\CommercePaymentMethod::GOOGLE_PAY
            )
        );
    }


    public function test_stripe_declares_google_pay_capability(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php'
        );
        self::assertIsString($stripe);

        self::assertStringContainsString('CommercePaymentMethod::GOOGLE_PAY', $stripe);
    }

}
