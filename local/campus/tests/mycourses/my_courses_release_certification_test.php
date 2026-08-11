<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/** Final static contract test for the native My Courses page. */
final class my_courses_release_certification_test extends advanced_testcase {
    public function test_my_courses_is_native_and_learning_focused(): void {
        global $CFG;

        $paths = [
            $CFG->dirroot . '/local/campus/mycourses.php',
            $CFG->dirroot . '/local/campus/classes/output/mycourses/MyCoursesPage.php',
            $CFG->dirroot . '/local/campus/templates/mycourses/page.mustache',
            $CFG->dirroot . '/local/campus/templates/mycourses/components/course_card.mustache',
        ];

        $source = '';
        foreach ($paths as $path) {
            $content = file_get_contents($path);
            $this->assertIsString($content, $path);
            $source .= "\n" . $content;
        }

        $this->assertStringNotContainsString('block_edly_course_filter', $source);
        $this->assertStringNotContainsString('access_info_map', $source);
        $this->assertStringNotContainsString('subscribe.php', $source);
        $this->assertStringNotContainsString('checkout.php?planid=', $source);
        $this->assertStringNotContainsString('commercialreference', $source);
        $this->assertStringNotContainsString('purchaseurl', $source);
        $this->assertStringContainsString('recommendations', $source);
        $this->assertStringContainsString('progress', $source);
    }
}
