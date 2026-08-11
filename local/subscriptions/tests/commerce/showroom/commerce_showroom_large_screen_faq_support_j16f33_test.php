<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_large_screen_faq_support_j16f33_test extends \advanced_testcase {
    public function test_large_screen_scene_selector_excludes_faq_and_support(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            ':not(.commerce-showroom-faq):not(.commerce-showroom-support)',
            $css
        );
    }

    public function test_large_screen_faq_support_have_no_viewport_min_height(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            ".commerce-showroom > .commerce-showroom-faq,\n    .commerce-showroom > .commerce-showroom-support {\n        display: block;\n        min-height: 0 !important;",
            $css
        );
    }

    public function test_faq_support_do_not_reserve_intrinsic_large_screen_space(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            'content-visibility: visible;',
            $css
        );
        self::assertStringContainsString(
            'contain-intrinsic-size: none;',
            $css
        );
    }
}
