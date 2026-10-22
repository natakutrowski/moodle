<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_articles_c4_dark_alternate_798c43_test extends \advanced_testcase {
    public function test_second_dark_preset_is_available_and_uses_white_text_contract(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertIsString($registry);
        self::assertIsString($presenter);
        self::assertIsString($css);

        self::assertStringContainsString("'dark2' => 'Sombre 2'", $registry);
        self::assertStringContainsString(
            "['default', 'white', 'light', 'soft', 'campuspink', 'dark', 'dark2', 'gradient', 'custom', 'image']",
            $presenter
        );
        self::assertStringContainsString(
            '.commerce-showroom .commerce-showroom-background--dark2 {',
            $css
        );
        self::assertStringContainsString('background: #1A1630;', $css);
        self::assertStringContainsString('.commerce-showroom-text--white', $css);
        self::assertStringContainsString(
            '.commerce-showroom-background--dark2 .commerce-showroom-heart-puzzle__heading h2',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-support.commerce-showroom-background--dark2 .commerce-showroom-support__card',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom > .commerce-showroom-background--dark + .commerce-showroom-background--dark2',
            $css
        );
    }
}
