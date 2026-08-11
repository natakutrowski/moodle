<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class customer_polish_j10e_test extends \advanced_testcase {
    public function test_courses_use_customer_routes_and_safe_course_hook(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
                . '/local/campus/classes/output/mycourses/'
                . 'MyCoursesPage.php'
        );
        $lib = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        self::assertStringContainsString(
            'UrlFactory::course',
            $page
        );
        self::assertStringContainsString(
            'CommerceCustomerPublicUrlResolver::product',
            $page
        );
        self::assertStringContainsString(
            'function local_campus_extend_navigation_course',
            $lib
        );
        self::assertStringNotContainsString(
            '$PAGE->navbar->prepend(',
            $lib
        );
    }
}
