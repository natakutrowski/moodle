<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_sticky_footer_j16l3_test extends \advanced_testcase {
    public function test_mobile_sticky_has_one_stable_geometry_rule_set(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringNotContainsString(
            '/* J16L1 — sticky footer desktop/mobile polish. */',
            $css
        );
        self::assertStringNotContainsString(
            '/* J16L2 — exact sticky selectors',
            $css
        );
        self::assertSame(
            1,
            substr_count($css, '/* J16L3 — sticky footer stabilisation.')
        );

        self::assertStringContainsString(
            'bottom: max(.3rem, env(safe-area-inset-bottom)) !important;',
            $css
        );
        self::assertStringContainsString(
            'padding: .26rem .3rem !important;',
            $css
        );
        self::assertStringContainsString(
            'align-items: center !important;',
            $css
        );
        self::assertStringContainsString(
            'height: 2.45rem !important;',
            $css
        );
    }
}
