<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** Structural certification of the J6.1 Legacy Trial to Native Storefront bridge. */
final class trial_native_conversion_bridge_test extends \advanced_testcase {
    public function test_homepage_trial_modal_is_preserved(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');

        $this->assertStringContainsString('id="campusTrialModal"', $source);
        $this->assertStringContainsString('local_campus_inject_trial_ui', $source);
        $this->assertStringContainsString("'local_campus/trial_popup'", $source);
        $this->assertStringContainsString('campusTrialForm', $source);
    }

    public function test_discount_banner_uses_native_conversion_bridge(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');

        $this->assertStringContainsString('local_campus_trial_conversion_url', $source);
        $this->assertStringContainsString('CommerceTrialConversionBridge', $source);
        $this->assertStringNotContainsString(
            '$subscribe = (new moodle_url(\'/boutique\'))->out(false);',
            $source
        );
    }
}
