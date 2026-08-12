<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression coverage for CampusFR course section restriction UX.
 *
 * @package theme_edly
 */
final class course_section_restriction_ux_test extends \advanced_testcase {

    public function test_theme_contains_concise_completion_and_date_messages(): void {
        $this->assertSame(
            'Complete step 4 to unlock this step',
            get_string('coursesection_complete_previous', 'theme_edly', 4)
        );
        $this->assertSame(
            'Available soon',
            get_string('coursesection_available_soon', 'theme_edly')
        );
    }

    public function test_availability_template_replaces_native_banner_for_custom_locks(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/core_courseformat/local/content/availability.mustache'
        );

        $this->assertStringContainsString('{{#hascampussectionrestriction}}', $template);
        $this->assertStringContainsString('{{#hascampussectioncover}}', $template);
        $this->assertStringContainsString('campus-section-restricted-cover', $template);
        $this->assertStringContainsString('{{campussectioncoverurl}}', $template);
        $this->assertStringContainsString('{{^hascampussectionrestriction}}', $template);
    }

    public function test_restricted_section_cover_has_visual_state(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/theme/edly/style/style.css');

        $this->assertStringContainsString('.campus-section-cover', $css);
        $this->assertStringContainsString('filter: grayscale(1)', $css);
        $this->assertStringContainsString('.campus-section-restricted-cover__message', $css);
        $this->assertStringContainsString('background: transparent !important', $css);
        $this->assertStringContainsString('position: absolute', $css);
    }
}
