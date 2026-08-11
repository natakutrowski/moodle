<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class guest_navigation_shared_j12b_test extends \advanced_testcase {
    public function test_shared_component_is_available_to_theme_and_landing(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/campus/templates/guest_navigation.mustache'
        );
        $source = file_get_contents(
            $CFG->dirroot
                . '/local/campus/amd/src/guest_navigation.js'
        );

        self::assertIsString($template);
        self::assertIsString($source);
        self::assertStringContainsString(
            'data-campus-guest-navigation',
            $template
        );
        self::assertStringContainsString(
            'data-campus-guest-action',
            $template
        );
        self::assertStringContainsString(
            'define([], function()',
            $source
        );
    }
}
