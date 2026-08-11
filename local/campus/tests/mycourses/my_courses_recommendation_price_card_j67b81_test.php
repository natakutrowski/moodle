<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.7B8.1 recommendation price-card layout contract. */
final class my_courses_recommendation_price_card_j67b81_test
        extends \advanced_testcase {

    public function test_all_price_modes_use_vertical_full_width_structure(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );
        $this->assertIsString($template);
        $this->assertStringContainsString(
            'campus-course-recommendation__footer',
            $template
        );
        $this->assertSame(
            3,
            substr_count(
                $template,
                'campus-course-recommendation__price-card--'
            )
        );
        $this->assertStringContainsString(
            'campus-course-recommendation__price-line',
            $template
        );
        $this->assertStringContainsString(
            'campus-course-recommendation__price-actions',
            $template
        );
        $this->assertStringContainsString(
            'campus-course-recommendation__discover-link',
            $template
        );
    }

    public function test_discount_and_discover_share_the_last_row(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );

        $actions = substr_count(
            $template,
            'campus-course-recommendation__price-actions'
        );
        $links = substr_count(
            $template,
            'campus-course-recommendation__discover-link'
        );

        $this->assertSame(3, $actions);
        $this->assertSame(3, $links);
        $this->assertStringContainsString(
            'fa-arrow-up-right-from-square',
            $template
        );
    }

    public function test_standard_price_label_is_exposed_by_context(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/campus/classes/output/mycourses/'
            . 'MyCoursesPage.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'mycourses_recommendation_standard_price',
            $source
        );
    }
}
