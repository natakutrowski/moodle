<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_mobile_guest_j13h22_test extends \advanced_testcase {
    public function test_guest_navigation_stays_visible_without_mobile_dropdown(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache');
        $css = file_get_contents($CFG->dirroot . '/theme/edly/style/customer-navigation.css');
        self::assertStringContainsString('{{#customernavigation.enabled}}', $template);
        self::assertStringContainsString('.showroom-shell__navigation > .campus-guest-nav', $css);
        self::assertStringContainsString('display: flex !important', $css);
    }
}
