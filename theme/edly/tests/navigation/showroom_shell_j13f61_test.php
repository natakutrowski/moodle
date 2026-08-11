<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_shell_j13f61_test extends \advanced_testcase {
    public function test_showroom_shell_keeps_requirements_pipeline_without_edly_footer(): void {
        global $CFG;
        $path = $CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('{{{ output.standard_end_of_body_html }}}', $source);
        self::assertStringNotContainsString('{{> theme_boost/footer }}', $source);
        self::assertStringNotContainsString('showroom-shell__footer', $source);

    }
}
