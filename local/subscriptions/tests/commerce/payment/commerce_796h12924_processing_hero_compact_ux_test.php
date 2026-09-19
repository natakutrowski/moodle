<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12924_processing_hero_compact_ux_test extends \advanced_testcase {
    public function test_processing_hero_uses_one_message_with_spinner(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            'commerce-order-hero__message--processing',
            $source
        );
        self::assertStringContainsString(
            'commerce_fulfillment_processing_message',
            $source
        );
        self::assertStringNotContainsString(
            'commerce_fulfillment_watch_message',
            $source
        );
    }

    public function test_manual_refresh_is_a_discreet_fallback_not_a_bootstrap_button(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/order_result.css'
        );

        self::assertStringContainsString(
            'commerce-order-fulfillment-watch__refresh',
            $source
        );
        self::assertStringNotContainsString(
            "'class' => 'btn btn-sm btn-outline-primary'",
            $source
        );
        self::assertStringContainsString(
            '.commerce-order-fulfillment-watch__refresh',
            $css
        );
        self::assertStringContainsString(
            'background: transparent;',
            $css
        );
    }

    public function test_processing_message_exists_in_all_checkout_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                "commerce_fulfillment_processing_message",
                $source
            );
        }
    }
}
