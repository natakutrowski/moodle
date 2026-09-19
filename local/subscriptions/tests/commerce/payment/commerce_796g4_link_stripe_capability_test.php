<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g4_link_stripe_capability_test extends \advanced_testcase {

    public function test_stripe_declares_link_capability(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php'
        );
        self::assertIsString($stripe);

        self::assertStringContainsString('CommercePaymentMethod::LINK', $stripe);
    }


    public function test_link_is_embedded_and_executable(): void {
        self::assertSame(
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method('link')
        );
        self::assertTrue(\local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now('link'));
    }

}
