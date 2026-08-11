<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o2_test extends \advanced_testcase {
    public function test_journey_badge_alignment_line_and_hero_rule_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            'fa-solid fa-mountain-sun',
            $template
        );

        self::assertStringContainsString(
            'font-size: clamp(2.25rem, 3.45vw, 3.4rem);',
            $css
        );
        self::assertStringContainsString(
            'align-items: center;',
            $css
        );
        self::assertStringContainsString(
            'left: calc(1.1rem + 22px);',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-journey__step:last-child',
            $css
        );
        self::assertStringContainsString(
            'display: block !important;',
            $css
        );
    }
}
