<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Guards the first-load smart anchor stabilisation for long showroom pages.
 */
final class commerce_showroom_smart_offer_anchor_first_load_fix_test extends advanced_testcase {
    public function test_smart_offer_anchor_materialises_lazy_sections_before_measurement(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($js);
        self::assertStringContainsString(
            'const prepareOfferAnchorLayout = () => {',
            $js
        );
        self::assertStringContainsString(
            "document.getElementById('showroom-offers')",
            $js
        );
        self::assertStringContainsString(
            "section.style.contentVisibility = 'visible';",
            $js
        );
        self::assertStringContainsString(
            "section.style.containIntrinsicSize = 'none';",
            $js
        );

        $prepare = strpos($js, 'return prepareOfferAnchorLayout().then(() => {');
        $measure = strpos($js, 'const rect = featured.getBoundingClientRect();');

        self::assertNotFalse($prepare);
        self::assertNotFalse($measure);
        self::assertLessThan(
            $measure,
            $prepare,
            'Lazy sections must be materialised before the featured offer is measured.'
        );
    }

    public function test_smart_offer_anchor_waits_for_two_animation_frames(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($js);
        self::assertMatchesRegularExpression(
            '/requestAnimationFrame\\(\\(\\) => \\{\\s*window\\.requestAnimationFrame\\(resolve\\);/s',
            $js
        );
    }

    public function test_showroom_keeps_initial_content_visibility_optimisation(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertIsString($css);
        self::assertStringContainsString(
            '@supports (content-visibility: auto)',
            $css
        );
        self::assertStringContainsString(
            'contain-intrinsic-size: auto 900px;',
            $css
        );
    }
}
