<?php

namespace local_subscriptions\tests\commerce\showroom;

/**
 * 7.98C.3.3.3 — generic cover Hero uses the visible viewport rather than width-driven height.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_hero_viewport_798c333_test extends \advanced_testcase {
    public function test_generic_cover_hero_is_viewport_anchored_and_full_artwork_override_is_present(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $this->assertIsString($css);

        $marker = '/* 7.98C.3.3.3 — viewport-anchored generic cover Hero.';
        $start = strpos($css, $marker);
        $this->assertNotFalse($start);
        $section = substr($css, $start);

        $this->assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover.has-cms-background',
            $section
        );
        $this->assertStringContainsString(
            'height: calc(100svh - var(--navbar-height, 70px));',
            $section
        );
        $this->assertStringContainsString('min-height: 680px;', $section);
        $this->assertStringContainsString('max-height: none;', $section);
        // Historical C3.3.3 viewport geometry remains, but C4.12 deliberately
        // overrides the image fitting so authored artwork is never cropped.
        $this->assertStringContainsString('bottom: .65rem;', $section);
        $stretchmarker = '/* 7.98C.4.12 — preserve the complete Hero artwork by stretching it to the Hero box.';
        $stretchstart = strpos($css, $stretchmarker);
        $this->assertNotFalse($stretchstart);
        $stretchsection = substr($css, $stretchstart);
        $this->assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover.has-cms-background::before',
            $stretchsection
        );
        $this->assertStringContainsString('background-position: center center;', $stretchsection);
        $this->assertStringContainsString('background-size: 100% 100%;', $stretchsection);
        $this->assertStringNotContainsString('.commerce-showroom--expedition', $stretchsection);
    }
}
