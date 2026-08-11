<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class guest_mobile_shared_j12b_test extends \advanced_testcase {
    public function test_mobile_navigation_uses_local_campus_partial(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/templates/guest_navigation_mobile.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'local_campus/guest_navigation',
            $template
        );
        self::assertStringNotContainsString(
            'campus-guest-mobile-compact',
            $template
        );
    }
}
