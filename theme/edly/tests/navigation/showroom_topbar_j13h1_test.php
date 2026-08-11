<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_topbar_j13h1_test extends \advanced_testcase {
    public function test_mobile_navigation_is_contained_in_showroom_menu(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache');
        self::assertIsString($source);
        self::assertStringContainsString('showroom-shell__mobile-menu', $source);
        self::assertStringContainsString('showroom-shell__mobile-menu-panel', $source);
        self::assertStringContainsString('theme_edly/customer_navigation_mobile', $source);
    }
}
