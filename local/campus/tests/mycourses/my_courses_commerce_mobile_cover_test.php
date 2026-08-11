<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class my_courses_commerce_mobile_cover_test extends \advanced_testcase {
    public function test_course_card_supports_a_mobile_commerce_cover(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/campus/templates/mycourses/components/course_card.mustache');
        $page = file_get_contents($CFG->dirroot . '/local/campus/classes/output/mycourses/MyCoursesPage.php');

        self::assertStringContainsString('<picture', $template);
        self::assertStringContainsString('hasmobileimage', $template);
        self::assertStringContainsString('MyCourseMobileCoverResolver', $page);
        self::assertStringNotContainsString('MyCourseCommerceCoverResolver', $page);

    }
}
