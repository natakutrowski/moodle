<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1_execution_policy_test extends \advanced_testcase {

    public function test_current_supported_methods_use_embedded_execution(): void {
        $embedded = \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
        foreach ([
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::CARD,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::PAYPAL,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::APPLE_PAY,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::GOOGLE_PAY,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::LINK,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::KLARNA,
            \local_subscriptions\commerce\payment\method\CommercePaymentMethod::SBP,
        ] as $method) {
            self::assertSame(
                $embedded,
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::mode_for_method($method)
            );
        }
    }


    public function test_supported_methods_are_executable_now_and_require_embedded_executor(): void {
        foreach ([
            'card', 'paypal', 'apple_pay', 'google_pay', 'link', 'klarna', 'sbp',
        ] as $method) {
            self::assertTrue(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::is_executable_now($method)
            );
            self::assertTrue(
                \local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy::requires_embedded_executor($method)
            );
        }
    }

}
