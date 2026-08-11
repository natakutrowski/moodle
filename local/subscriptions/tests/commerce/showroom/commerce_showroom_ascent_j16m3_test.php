<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_ascent_j16m3_test extends \advanced_testcase {
    public function test_gradient_markers_and_progress_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');

        self::assertIsString($registry);
        self::assertIsString($presenter);
        self::assertIsString($template);
        self::assertIsString($css);
        self::assertIsString($js);

        self::assertStringContainsString("'gradientstart'", $registry);
        self::assertStringContainsString("'gradientend'", $registry);
        self::assertStringContainsString('ascentgradientstart', $presenter);
        self::assertStringContainsString('--showroom-ascent-gradient-start', $template);
        self::assertStringNotContainsString('<circle cx="10" cy="152"', $template);
        self::assertStringContainsString('commerce-showroom-ascent__gradient-end', $template);

        // Current contract: JS computes the progressive colour and exposes it
        // through a CSS variable. Icons inherit it instead of using clipped text.
        self::assertStringContainsString('--showroom-ascent-icon-color', $css);
        self::assertStringContainsString('color: inherit !important;', $css);
        self::assertStringContainsString(
            '.commerce-showroom-ascent__marker--2 { left: 24.2%; top: 62.1%; }',
            $css
        );
        self::assertStringContainsString('const updateDynamicColors = (ratio) => {', $js);
        self::assertStringContainsString(
            "card.style.setProperty('--showroom-ascent-icon-color', color);",
            $js
        );
        self::assertStringContainsString(
            "window.matchMedia('(max-width: 1100px)').matches",
            $js
        );
    }
}
