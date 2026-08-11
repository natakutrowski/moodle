<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_showroom_offer_spacing_j16b_test extends \advanced_testcase {
    public function test_builder_exposes_canonical_offer_card_spacing(): void {
        $root = dirname(__DIR__, 3);
        $registry = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . '/templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString("self::select('cardstopspacing'", $registry);
        self::assertStringContainsString('offerscardstopspacingclass', $presenter);
        self::assertStringContainsString('{{offerscardstopspacingclass}}', $template);
        self::assertStringContainsString('--showroom-offers-cards-top-spacing', $css);
        self::assertStringContainsString('margin-top: var(--showroom-offers-cards-top-spacing) !important;', $css);
    }
}
