<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o1_test extends \advanced_testcase {
    public function test_stage_method_supports_background_controls_and_six_steps(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );
        $defaults = file_get_contents(
            $root . 'classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            "self::media('backgroundurl', 'Image de fond', 'image')",
            $registry
        );
        self::assertStringContainsString("'backgroundopacity'", $registry);
        self::assertStringContainsString("'backgroundblur'", $registry);
        self::assertStringContainsString('hasjourneybackground', $presenter);
        self::assertStringContainsString('journeybackgroundopacity', $presenter);
        self::assertStringContainsString('ВОСХОЖДЕНИЕ ИЗ 30 ЭТАПОВ', $defaults);
        self::assertStringContainsString('Отправляемся на привал', $defaults);
        self::assertStringContainsString('commerce-showroom-stage-method__background', $template);
        self::assertStringContainsString('--journey-bg-opacity', $template);
        self::assertStringContainsString('filter: blur(var(--journey-bg-blur, 0px));', $css);
    }
}
