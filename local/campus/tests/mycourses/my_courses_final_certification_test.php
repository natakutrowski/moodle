<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** Final structural certification for the native My courses experience. */
final class my_courses_final_certification_test extends \advanced_testcase {
    public function test_page_is_native_and_keeps_recommendation_contract(): void {
        global $CFG;

        $controller = file_get_contents($CFG->dirroot . '/local/campus/mycourses.php');
        $page = file_get_contents($CFG->dirroot . '/local/campus/templates/mycourses/page.mustache');
        $styles = file_get_contents($CFG->dirroot . '/local/campus/styles.css');

        $this->assertIsString($controller);
        $this->assertStringNotContainsString('block_edly_course_filter', $controller);
        $this->assertStringContainsString('CommerceCourseRecommendationService', $controller);
        $this->assertStringContainsString('mycourses/components/recommendations', $page);
        $this->assertStringContainsString('aspect-ratio: 3 / 4', $styles);
        $this->assertStringContainsString('object-fit: cover', $styles);
    }
}
