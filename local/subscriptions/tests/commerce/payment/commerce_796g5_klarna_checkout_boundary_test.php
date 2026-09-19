<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g5_klarna_checkout_boundary_test extends \advanced_testcase {

    public function test_klarna_is_now_embedded_and_executable(): void {
        self::assertSame(
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method('klarna')
        );
        self::assertTrue(\local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now('klarna'));
    }


    public function test_stripe_declares_klarna_capability(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php'
        );
        self::assertIsString($stripe);

        self::assertStringContainsString('CommercePaymentMethod::KLARNA', $stripe);
    }

}
