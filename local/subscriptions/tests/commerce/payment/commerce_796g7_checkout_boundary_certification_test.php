<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g7_checkout_boundary_certification_test extends \advanced_testcase {

    public function test_current_global_stripe_methods_are_not_filtered_from_checkout(): void {
        foreach (['apple_pay', 'google_pay', 'link', 'klarna'] as $method) {
            self::assertTrue(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now($method),
                $method
            );
        }
    }


    public function test_current_global_methods_use_embedded_execution(): void {
        foreach (['card', 'paypal', 'apple_pay', 'google_pay', 'link', 'klarna', 'sbp'] as $method) {
            self::assertSame(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method($method),
                $method
            );
        }
    }

}
