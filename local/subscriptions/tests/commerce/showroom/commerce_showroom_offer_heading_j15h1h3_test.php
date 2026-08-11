<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static certification checks for the Builder-driven offers heading. */
final class commerce_showroom_offer_heading_j15h1h3_test extends \advanced_testcase {
    public function test_offer_heading_fields_are_translatable_and_rendered(): void {
        $root = dirname(__DIR__, 3);
        $registry = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $defaults = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php');
        $presenter = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . '/templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString("self::text('badge'", $registry);
        self::assertStringContainsString("self::text('titlehighlight'", $registry);
        self::assertStringContainsString("self::textarea('text', 'Sous-titre')", $registry);
        self::assertStringContainsString("commerce_showroom_offers_title_highlight", $defaults);
        self::assertStringContainsString("'offerstitlehighlight'", $presenter);
        self::assertStringContainsString('commerce-showroom-offers__badge', $template);
        self::assertStringContainsString('commerce-showroom-offers__title-highlight', $template);
        self::assertStringContainsString('justify-items: center', $css);
        self::assertStringContainsString('translateY(-.22rem) scale(1.025)', $css);
    }
}
