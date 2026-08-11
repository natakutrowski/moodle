<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m5_test extends \advanced_testcase {
    public function test_marker_geometry_color_picker_and_mobile_progress_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $showroomjs = file_get_contents($root . 'amd/src/showroom.js');
        $builderjs = file_get_contents($root . 'js/showroom_builder.js');

        self::assertStringContainsString("self::color(", $registry);
        self::assertStringContainsString("'type' => 'color'", $registry);
        self::assertStringContainsString("field.type === 'color'", $builderjs);
        self::assertStringContainsString("input.type = 'color'", $builderjs);

        self::assertStringContainsString(
            'M100 152 L170 142 L230 146 L300 120',
            $template
        );
        self::assertStringContainsString(
            '.commerce-showroom-ascent__marker--1 { left: 10%; top: 80%; }',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-ascent__marker--5 { left: 90%; top: 10.53%; }',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-ascent__marker > span::before,',
            $css
        );
        self::assertStringContainsString(
            'background: rgba(255, 255, 255, .42);',
            $css
        );
        self::assertStringContainsString(
            'const summitOffset = rect.height * (20 / 190);',
            $showroomjs
        );
        self::assertStringNotContainsString(
            '!ascent || prefersReducedMotion() || ascent.dataset.showroomScrollBound',
            $showroomjs
        );
    }
}
