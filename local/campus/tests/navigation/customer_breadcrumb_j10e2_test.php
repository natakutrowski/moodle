<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class customer_breadcrumb_j10e2_test extends \advanced_testcase {
    public function test_course_hook_does_not_mutate_navigation_during_build(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'function local_campus_extend_navigation_course',
            $source
        );
        $this->assertStringContainsString(
            "js_call_amd('local_campus/courseindex_subsections', 'init')",
            $source
        );
        $this->assertStringNotContainsString(
            '$PAGE->navbar->prepend(',
            $source
        );
        $this->assertStringNotContainsString(
            'iterator_to_array($PAGE->navbar->children)',
            $source
        );
    }
}
