<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o11_test extends \advanced_testcase {
    public function test_final_journey_crop_layers_and_mobile_rows(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('width: 111.1112% !important;', $css);
        self::assertStringContainsString('z-index: 3 !important;', $css);
        self::assertStringContainsString('grid-template-rows: auto auto !important;', $css);
        self::assertStringContainsString('grid-row: 2 !important;', $css);
        self::assertStringContainsString(
            'padding-top: clamp(1.25rem, 2.2vw, 2rem);',
            $css
        );
        self::assertStringContainsString(
            '/* J16O16 — mobile Journey: keep centered heading + micro crossfade at image seam. */',
            $css
        );
    }
}
