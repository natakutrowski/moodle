<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Static certification checks for J15H.1H. */
final class commerce_showroom_mobile_presentation_j15h1h_test extends \advanced_testcase {
    public function test_builder_and_public_renderer_expose_mobile_presentation(): void {
        $root = dirname(__DIR__, 3);
        $registry = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents($root . '/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');
        $template = file_get_contents($root . '/templates/showroom/default.mustache');
        $css = file_get_contents($root . '/styles/showroom.css');

        self::assertStringContainsString("'mobilepresentation'", $registry);
        self::assertStringContainsString("commerce-showroom-offers--mobile-", $presenter);
        self::assertStringContainsString('{{offersmobilepresentationclass}}', $template);
        self::assertStringContainsString('commerce-showroom-offers__swipe-hint', $template);
        self::assertStringContainsString('.commerce-showroom-offers--mobile-stack', $css);
        self::assertStringContainsString('.commerce-showroom-offers--mobile-slider', $css);
        self::assertStringNotContainsString('.commerce-showroom-offer--bundle { order:', $css);
        self::assertStringNotContainsString('.commerce-showroom-offer--course { order:', $css);
        self::assertStringNotContainsString('.commerce-showroom-offer--pdf { order:', $css);
    }
}
