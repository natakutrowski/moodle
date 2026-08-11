<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_support_center_spacing_j16f31_test extends \advanced_testcase {
    public function test_support_uses_vertical_centered_layout_on_desktop_too(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            ".commerce-showroom-support__card {\n    display: flex;\n    flex-direction: column;\n    align-items: center;",
            $css
        );
        self::assertStringContainsString(
            'text-align: center;',
            $css
        );
        self::assertStringContainsString(
            'justify-content: center;',
            $css
        );
    }

    public function test_faq_support_spacing_is_overridden_for_wide_screens(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            '@media (min-width: 1400px)',
            $css
        );
        self::assertStringContainsString(
            'padding-bottom: 1rem !important;',
            $css
        );
        self::assertStringContainsString(
            'padding-top: .55rem !important;',
            $css
        );
    }
}
