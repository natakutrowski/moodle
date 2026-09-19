<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12731_unselected_checkout_availability_test extends \advanced_testcase {
    public function test_unselected_checkout_remains_available_when_methods_exist(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            '$hasavailablepaymentmethod =',
            $checkout
        );
        self::assertStringContainsString(
            '$orderedmethods !== [];',
            $checkout
        );
        self::assertStringContainsString(
            '|| !$hasavailablepaymentmethod;',
            $checkout
        );
    }

    public function test_no_method_message_is_based_on_global_availability(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            ": (!\$hasavailablepaymentmethod",
            $checkout
        );
    }

    public function test_alfa_widget_has_no_unused_terms_binding(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringNotContainsString(
            "const terms =\n        form.querySelector",
            $amd
        );
    }
}
