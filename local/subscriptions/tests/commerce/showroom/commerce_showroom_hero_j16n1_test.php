<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_hero_j16n1_test extends \advanced_testcase {
    public function test_hero_supports_three_cms_images_and_dynamic_stats(): void {
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
        $js = file_get_contents($root . 'amd/src/showroom.js');

        self::assertStringContainsString(
            "self::media('backgroundurl', 'Image de fond du Hero', 'image')",
            $registry
        );
        self::assertStringContainsString(
            "self::media('desktopimageurl'",
            $registry
        );
        self::assertStringContainsString(
            "self::media('mobileimageurl'",
            $registry
        );
        self::assertStringContainsString('hasherodesktopimage', $presenter);
        self::assertStringContainsString('hasheromobileimage', $presenter);
        self::assertStringContainsString('commerce-showroom-device__cms-image--desktop', $template);
        self::assertStringContainsString('commerce-showroom-device__cms-image--mobile', $template);
        self::assertStringContainsString('commerce-showroom-hero__title-accent', $template);
        self::assertStringContainsString('10+|форматов практики', $defaults);
        self::assertStringContainsString('data-showroom-counter', $template);
        self::assertStringContainsString('const bindCounters = () => {', $js);
        self::assertStringContainsString(
            'var(--showroom-hero-background) center / cover no-repeat;',
            $css
        );
    }
}
