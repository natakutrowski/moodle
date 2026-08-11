<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** Static integration guards for 7.95H4.1. */
final class commerce_795h41_payment_journey_stabilisation_test extends \advanced_testcase {
    public function test_legacy_stepper_selectors_are_neutralised(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-checkout-steps__list .commerce-checkout-steps__item:not(:last-child)::after',
            $css
        );
        self::assertStringContainsString('content: none;', $css);
        self::assertStringContainsString(
            '.commerce-checkout-steps__list .commerce-checkout-steps__label',
            $css
        );
        self::assertStringContainsString('overflow: visible;', $css);
        self::assertStringContainsString('white-space: normal;', $css);
    }

    public function test_provider_error_message_has_a_safe_fallback(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php');
        self::assertIsString($source);
        self::assertStringContainsString(
            "string_exists('commerce_checkout_launch_error_reference', 'local_subscriptions')",
            $source
        );
        self::assertStringContainsString("get_string('commerce_checkout_launch_error', 'local_subscriptions')", $source);
    }

    public function test_reference_string_exists_in_all_supported_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertIsString($source);
            self::assertStringContainsString("\$string['commerce_checkout_launch_error_reference']", $source);
        }
    }
}
