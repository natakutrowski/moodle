<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.98C.4.12 — generic cover Hero keeps the complete CMS artwork visible.
 *
 * @coversNothing
 */
final class commerce_showroom_hero_full_background_798c412_test extends \advanced_testcase {
    public function test_final_css_override_stretches_generic_cover_background_without_crop(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $this->assertIsString($css);

        $marker = '/* 7.98C.4.12 — preserve the complete Hero artwork by stretching it to the Hero box.';
        $start = strpos($css, $marker);
        $this->assertNotFalse($start);
        $section = substr($css, $start);

        $this->assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover.has-cms-background {',
            $section
        );
        $this->assertStringContainsString(
            '.commerce-showroom--generic .commerce-showroom-hero--cover.has-cms-background::before {',
            $section
        );
        $this->assertStringContainsString('background-position: center center;', $section);
        $this->assertStringContainsString('background-size: 100% 100%;', $section);
        $this->assertStringContainsString('background-repeat: no-repeat;', $section);
        $this->assertStringNotContainsString('background-size: cover;', $section);
    }
}
