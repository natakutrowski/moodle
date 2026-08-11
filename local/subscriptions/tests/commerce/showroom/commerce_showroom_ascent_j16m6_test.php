<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m6_test extends \advanced_testcase {
    public function test_route_points_have_no_halo_and_progress_respects_topbar(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');

        self::assertStringContainsString(
            '/* J16M6 — route point cleanup + optical 4px alignment. */',
            $css
        );
        self::assertStringContainsString(
            'top: -4px !important;',
            $css
        );
        self::assertStringContainsString(
            'box-shadow: none !important;',
            $css
        );
        self::assertStringContainsString(
            'filter: none !important;',
            $css
        );

        self::assertStringContainsString(
            'const campusTopbarHeight = 150;',
            $js
        );
        self::assertStringContainsString(
            'const endTop = campusTopbarHeight - summitOffset;',
            $js
        );
    }
}
