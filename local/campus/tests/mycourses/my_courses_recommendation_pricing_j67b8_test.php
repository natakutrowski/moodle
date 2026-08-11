<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.7B8 recommendation UI harmonisation contract. */
final class my_courses_recommendation_pricing_j67b8_test
        extends \advanced_testcase {

    public function test_template_keeps_product_title_before_upgrade_panel(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );
        $title = strpos($template, '<h3>');
        $footer = strpos(
            $template,
            'campus-course-recommendation__footer'
        );
        $upgrade = strpos(
            $template,
            'campus-course-recommendation__upgrade-panel'
        );
        $this->assertIsInt($title);
        $this->assertIsInt($footer);
        $this->assertIsInt($upgrade);
        $this->assertLessThan($footer, $title);
        $this->assertLessThan($upgrade, $footer);
    }

    public function test_discovery_and_upgrade_use_same_price_structure(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/campus/templates/mycourses/components/'
            . 'recommendations.mustache'
        );

        $this->assertStringContainsString(
            'campus-course-recommendation__price-card--discovery',
            $template
        );
        $this->assertStringContainsString(
            'campus-course-recommendation__price-card--upgrade',
            $template
        );
        $this->assertStringContainsString(
            '{{upgradediscountpercentage}}%',
            $template
        );
        $this->assertStringContainsString(
            '{{trialdiscountpercentage}}%',
            $template
        );
    }

    public function test_context_suppresses_upgrade_for_trial_and_bundle(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/campus/classes/output/mycourses/'
            . 'MyCoursesPage.php'
        );

        $this->assertStringContainsString(
            "&& empty(\$context['trialoffer'])",
            $source
        );
        $this->assertStringContainsString(
            "(\$context['type'] ?? '') !== 'bundle'",
            $source
        );
    }
}
