<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_topbar_j13h21_test extends \advanced_testcase {
    public function test_showroom_shell_keeps_guest_and_member_topbars_without_footer(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache');

        self::assertIsString($source);
        self::assertStringContainsString('{{> theme_edly/customer_navigation }}', $source);
        self::assertStringContainsString('{{> theme_edly/guest_navigation }}', $source);
        self::assertStringContainsString('{{{ output.standard_end_of_body_html }}}', $source);
        self::assertStringNotContainsString('{{> theme_boost/footer }}', $source);
    }
}
