<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o9_test extends \advanced_testcase {
    public function test_journey_uses_local_continuous_segments_under_markers(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString(
            '/* J16O13 — local Journey segments: no parent rail, no cross-context z-index fight. */',
            $css
        );
        self::assertStringContainsString('--journey-line-x:', $css);
        self::assertStringContainsString(
            '.commerce-showroom-stage-method .commerce-showroom-journey__step::before',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-stage-method .commerce-showroom-journey__step::after',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-stage-method .commerce-showroom-journey__marker {',
            $css
        );
        self::assertStringContainsString('z-index: 3 !important;', $css);
    }
}
