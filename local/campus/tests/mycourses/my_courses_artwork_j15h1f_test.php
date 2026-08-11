<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/** Static regression checks for the J15H.1F My Courses artwork policy. */
final class my_courses_artwork_j15h1f_test extends \advanced_testcase {
    public function test_mobile_commerce_cover_resolution_supports_native_resource_formats(): void {
        $resolver = file_get_contents(__DIR__ . '/../../classes/mycourses/MyCourseMobileCoverResolver.php');
        $service = file_get_contents(__DIR__ . '/../../classes/mycourses/MyCourseMobileCoverService.php');

        self::assertIsString($resolver);
        self::assertIsString($service);
        self::assertStringContainsString('MyCourseMobileCoverService', $resolver);
        self::assertStringContainsString("course_mobile_cover", $service);
        self::assertStringContainsString("context_course::instance", $service);

    }

    public function test_responsive_css_removes_recommendation_gap_and_compacts_desktop_cards(): void {
        $css = file_get_contents(__DIR__ . '/../../styles.css');

        self::assertIsString($css);
        self::assertStringContainsString('grid-template-columns: 190px minmax(0, 1fr)', $css);
        self::assertStringContainsString('aspect-ratio: 4 / 5', $css);
        self::assertStringContainsString('.campus-course-recommendation__visual {', $css);
        self::assertStringContainsString('padding: 4px', $css);


    }
}
