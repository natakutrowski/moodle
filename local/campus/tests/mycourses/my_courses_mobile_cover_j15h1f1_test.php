<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use local_campus\mycourses\MyCourseMobileCoverService;

final class my_courses_mobile_cover_j15h1f1_test extends \advanced_testcase {
    public function test_service_resolves_a_course_scoped_mobile_cover(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => MyCourseMobileCoverService::COMPONENT,
            'filearea' => MyCourseMobileCoverService::FILEAREA,
            'itemid' => $course->id,
            'filepath' => '/',
            'filename' => 'mobile-cover.png',
        ], 'fake image content');

        $service = new MyCourseMobileCoverService();
        $url = $service->get_url((int)$course->id);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/pluginfile.php/', $url);
        $this->assertStringContainsString('/local_campus/course_mobile_cover/', $url);
        $this->assertStringContainsString($file->get_filename(), $url);
    }

    public function test_page_uses_local_mobile_cover_without_commerce_dependency(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/classes/output/mycourses/MyCoursesPage.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/campus/classes/mycourses/MyCourseMobileCoverResolver.php'
        );

        $this->assertStringContainsString('MyCourseMobileCoverResolver', $source);
        $this->assertStringNotContainsString('MyCourseCommerceCoverResolver', $source);
        $this->assertStringContainsString('MyCourseMobileCoverService', $resolver);
        $this->assertStringNotContainsString('local_subscriptions', $resolver);
    }

    public function test_course_card_keeps_desktop_and_mobile_ratios(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/campus/styles.css');

        $this->assertStringContainsString('grid-template-columns: 190px minmax(0, 1fr)', $css);
        $this->assertStringContainsString('aspect-ratio: 4 / 5', $css);
        $this->assertStringContainsString('aspect-ratio: 4 / 3', $css);
    }
}
