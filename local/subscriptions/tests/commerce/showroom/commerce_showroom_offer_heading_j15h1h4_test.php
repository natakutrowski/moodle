<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static certification checks for the compact multilingual tariff heading. */
final class commerce_showroom_offer_heading_j15h1h4_test extends \advanced_testcase {
    public function test_title_suffix_and_compact_offer_layout_are_present(): void {
        $root = dirname(__DIR__, 3);
        $registry = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $defaults = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php');
        $presenter = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . '/templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString("self::text('titlesuffix'", $registry);
        self::assertStringContainsString('commerce_showroom_offers_title_suffix', $defaults);
        self::assertStringContainsString("'offertitlesuffix'", $presenter);
        self::assertStringContainsString('commerce-showroom-offers__title-suffix', $template);
        self::assertStringContainsString('white-space: nowrap', $css);
        self::assertStringContainsString('border-radius: 23px 23px 0 0 !important', $css);
        self::assertStringContainsString('scale(1.025)', $css);
    }
}
