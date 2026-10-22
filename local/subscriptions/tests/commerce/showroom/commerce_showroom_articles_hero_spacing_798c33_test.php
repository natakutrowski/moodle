<?php

namespace local_subscriptions\tests\commerce\showroom;

/**
 * 7.98C.3.3/3.3.1/3.3.2 — generic Articles Hero spacing remains scoped away from Verbs.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_hero_spacing_798c33_test extends \advanced_testcase {
    public function test_generic_cover_hero_aligns_copy_with_stats_and_keeps_expedition_profile_untouched(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $this->assertIsString($css);

        $marker = '/* 7.98C.3.3 — generic cover Hero spacing polish.';
        $start = strpos($css, $marker);
        $this->assertNotFalse($start);
        $section = substr($css, $start);

        $this->assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover .commerce-showroom-hero__grid',
            $section
        );
        $this->assertStringContainsString('width: min(1180px, 100%);', $section);
        $this->assertStringContainsString('margin-inline: auto;', $section);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 740px);', $section);
        $this->assertStringContainsString('max-width: 740px;', $section);
        $this->assertStringContainsString('padding: 1rem 0 1.35rem;', $section);
        $this->assertStringContainsString('max-width: none;', $section);
        $this->assertStringContainsString('position: absolute;', $section);
        $this->assertStringContainsString('bottom: 1.1rem;', $section);
        $this->assertStringContainsString('height: clamp(650px, 55vw, 820px);', $section);
        $this->assertStringContainsString('height: 100%;', $section);
        $this->assertStringContainsString('min-height: 0;', $section);
        $this->assertStringContainsString('background-position: center center;', $section);
        $this->assertStringContainsString('background-size: cover;', $section);
        $this->assertStringNotContainsString('max-width: 13.5ch;', $section);
        $this->assertStringNotContainsString('.commerce-showroom--expedition', $section);
    }
}
