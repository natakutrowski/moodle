<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class guest_mobile_j12a_test extends \advanced_testcase {
    public function test_guest_mobile_navigation_uses_compact_native_controls(): void {
        $template = file_get_contents(__DIR__ . '/../../templates/guest_navigation_mobile.mustache');
        self::assertStringContainsString('local_campus/guest_navigation', $template);
        self::assertStringNotContainsString('offcanvas', $template);
    }

    public function test_guest_header_does_not_render_menu_button(): void {
        $navbar = file_get_contents(__DIR__ . '/../../templates/theme_boost/navbar.mustache');
        self::assertStringContainsString('guest_navigation_mobile', $navbar);
        self::assertStringNotContainsString('{{#str}}mobilemenu_button, theme_edly{{/str}}', $navbar);
    }
}
