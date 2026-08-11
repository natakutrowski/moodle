<?php

declare(strict_types=1);

namespace local_campus;

final class customer_navigation_j10d_test extends \advanced_testcase {
    public function test_my_courses_bootstrap_and_breadcrumb_are_safe(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/campus/mycourses.php');
        $this->assertStringContainsString('require_once(__DIR__', $source);
        $this->assertStringContainsString('UrlFactory::my_campus()', $source);
        $this->assertStringContainsString('$PAGE->navbar->ignore_active()', $source);
    }
}
