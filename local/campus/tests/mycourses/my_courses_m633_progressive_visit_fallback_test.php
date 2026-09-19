<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class my_courses_m633_progressive_visit_fallback_test extends \advanced_testcase {
    public function test_legacy_visit_completion_fallback_is_disabled_for_progressive_access(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/classes/mycourses/MyCoursesService.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            "\$progress === null && empty(\$status['isprogressive'])",
            $source
        );
        self::assertStringContainsString(
            'opening the first released lesson must',
            $source
        );
    }
}
