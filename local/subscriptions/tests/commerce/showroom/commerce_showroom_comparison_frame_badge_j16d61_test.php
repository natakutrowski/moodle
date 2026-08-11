<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_comparison_frame_badge_j16d61_test extends \advanced_testcase {
    public function test_desktop_table_has_one_rounded_outer_frame(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertIsString($css);
        self::assertStringContainsString(
            ".commerce-showroom-comparison__table {\n    position: relative;\n    min-width: 760px;\n    overflow: visible;\n    margin: 0;\n    border: 1px solid var(--showroom-comparison-frame);",
            $css
        );
        self::assertStringContainsString(
            ".commerce-showroom-comparison__scroller {\n    overflow-x: auto;\n    padding: 1.4rem .25rem .2rem;\n    background: transparent;\n    border: 0;",
            $css
        );
    }

    public function test_desktop_grid_is_darker(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            'border-top-color: rgba(52, 65, 78, .17);',
            $css
        );
        self::assertStringContainsString(
            'border-left-color: rgba(52, 65, 78, .17);',
            $css
        );
    }

    public function test_mobile_badge_is_centred_over_offer_column(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            ".commerce-showroom-comparison-mobile__recommended {\n        top: -.62rem;\n        left: 70%;",
            $css
        );
        self::assertStringContainsString(
            'overflow: visible;',
            $css
        );
    }
}
