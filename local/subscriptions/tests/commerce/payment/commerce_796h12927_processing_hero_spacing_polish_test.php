<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12927_processing_hero_spacing_polish_test extends \advanced_testcase {
    public function test_processing_message_spacing_is_compact(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/order_result.css'
        );

        self::assertStringContainsString(
            '.commerce-order-hero__message--processing {',
            $css
        );
        self::assertStringContainsString(
            'gap: .35rem;',
            $css
        );
        self::assertStringContainsString(
            'margin-left: .2rem !important;',
            $css
        );
    }

    public function test_refresh_link_is_closer_and_more_readable(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/order_result.css'
        );

        self::assertStringContainsString(
            'margin: .15rem 0 0 1.55rem;',
            $css
        );
        self::assertStringContainsString(
            'font-size: .84rem;',
            $css
        );
    }
}
