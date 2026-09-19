<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h3_wallet_execution_policy_test extends \advanced_testcase {

    public function test_link_and_klarna_are_currently_embedded_executable_methods(): void {
        foreach (['link', 'klarna'] as $method) {
            self::assertSame(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method($method)
            );
            self::assertTrue(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now($method)
            );
        }
    }

}
