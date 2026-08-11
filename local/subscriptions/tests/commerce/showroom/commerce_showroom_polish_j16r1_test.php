<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_polish_j16r1_test extends \advanced_testcase {
    public function test_mobile_journey_and_final_spacing_contract(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            '/* J16R1 — mobile spacing polish: Journey → Offers + Final legal bottom edge. */',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-stage-method.commerce-showroom-spacing--normal',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-final {',
            $css
        );
        self::assertStringContainsString(
            'padding-bottom: 0 !important;',
            $css
        );
    }
}
