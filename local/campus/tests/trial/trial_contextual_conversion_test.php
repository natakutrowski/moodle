<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.4 contextual Trial banner contract. */
final class trial_contextual_conversion_test extends \advanced_testcase {
    public function test_course_pages_use_a_contextual_trial_cta(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'trial_discount_banner_cta_current_course',
            $source
        );
        $this->assertStringContainsString(
            'resolve_for_course',
            $source
        );
        $this->assertStringContainsString(
            "new moodle_url('/boutique')",
            $source
        );
    }
}
