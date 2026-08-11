<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_mobile_guest_j13h23_test extends \advanced_testcase {
    public function test_showroom_reuses_shared_guest_navigation_on_mobile(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache');
        self::assertIsString($source);
        self::assertStringContainsString('{{> local_campus/guest_navigation }}', $source);
        self::assertStringContainsString('showroom-shell__guest-mobile', $source);
    }
}
