<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_polish_j16r3_test extends \advanced_testcase {
    public function test_r3_shared_background_and_visual_polish_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $registry = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $defaults = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php');
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString("'campuspink' => 'Rose très pâle CampusFR'", $registry);
        self::assertStringContainsString("self::color('sectionbackgroundcolor'", $registry);
        self::assertStringContainsString("self::media('sectionbackgroundimageurl'", $registry);
        self::assertStringContainsString("self::range('sectionbackgroundopacity'", $registry);
        self::assertStringContainsString("self::range('sectionbackgroundblur'", $registry);
        self::assertStringContainsString("'custom', 'image'", $presenter);
        self::assertStringContainsString('commerce-showroom-common-background', $template);

        self::assertStringContainsString("'secondarylabel' => 'Посмотреть видео о тренажёре'", $defaults);
        self::assertStringContainsString("'secondarylabel' => 'Découvrir l’entraîneur en vidéo'", $defaults);
        self::assertStringContainsString("'secondarylabel' => 'Watch the trainer video'", $defaults);

        self::assertStringContainsString('/* J16R3 — shared background controls and final showroom polish. */', $css);
        self::assertStringContainsString('.commerce-showroom-background--campuspink', $css);
        self::assertStringContainsString('.commerce-showroom-background--custom', $css);
        self::assertStringContainsString('.commerce-showroom-background--image', $css);
        self::assertStringContainsString('.commerce-showroom-expedition-card__icon,', $css);
        self::assertStringContainsString('text-shadow:', $css);
        self::assertStringContainsString('max-width: none;', $css);
    }
}
