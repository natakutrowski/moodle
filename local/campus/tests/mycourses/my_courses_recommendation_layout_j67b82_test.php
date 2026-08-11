<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.7B8.2 final recommendation composition. */
final class my_courses_recommendation_layout_j67b82_test
        extends \advanced_testcase {

    public function test_price_is_rendered_before_upgrade_panel(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );

        $price = strpos(
            $template,
            'campus-course-recommendation__footer'
        );
        $upgrade = strpos(
            $template,
            'campus-course-recommendation__upgrade-panel'
        );

        $this->assertIsInt($price);
        $this->assertIsInt($upgrade);
        $this->assertLessThan($upgrade, $price);
    }

    public function test_final_price_and_compare_price_share_one_line(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );

        $this->assertStringContainsString(
            'campus-course-recommendation__price-line',
            $template
        );
        $this->assertStringContainsString(
            '<strong>{{upgradepriceformatted}}</strong>',
            $template
        );
        $this->assertStringContainsString(
            '<del>{{upgradecomparepriceformatted}}</del>',
            $template
        );
    }

    public function test_upgrade_panel_has_access_and_saving_sections(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );
        $this->assertIsString($template);
        $this->assertStringContainsString(
            'campus-course-recommendation__upgrade-panel',
            $template
        );
        $this->assertStringContainsString(
            'campus-course-recommendation__upgrade-section--saving',
            $template
        );
        $this->assertStringContainsString('{{upgradesavinglabel}}', $template);
        $this->assertStringContainsString('{{upgradesavingtext}}', $template);
    }
}
