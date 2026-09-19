<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12732_context_provider_without_selection_test extends \advanced_testcase {
    public function test_checkout_keeps_method_unselected_but_context_provider_nonempty(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            '$selectedmethod = in_array(',
            $checkout
        );
        self::assertStringContainsString(
            "? \$requestedmethod\n    : '';",
            $checkout
        );
        self::assertStringContainsString(
            '$contextprovideravailability =',
            $checkout
        );
        self::assertStringContainsString(
            '$selectedavailability',
            $checkout
        );
        self::assertStringContainsString(
            'reset($orderedmethods)',
            $checkout
        );
        self::assertStringContainsString(
            "?? 'stripe';",
            $checkout
        );
    }

    public function test_context_provider_fallback_does_not_change_selected_method(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $selection = strpos(
            $checkout,
            '$selectedmethod = in_array('
        );
        $provider = strpos(
            $checkout,
            '$contextprovideravailability ='
        );

        self::assertNotFalse($selection);
        self::assertNotFalse($provider);
        self::assertLessThan(
            $provider,
            $selection
        );
    }
}
