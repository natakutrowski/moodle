<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Contract checks for J16A.1 Bundle editorial hierarchy. */
final class commerce_showroom_bundle_editorial_j16a1_test extends \advanced_testcase {
    public function test_bundle_badge_is_configurable(): void {
        $registry = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php');
        $presenter = file_get_contents(__DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php');

        self::assertStringContainsString("'bundlefeaturedlabel'", $registry);
        self::assertStringContainsString("'bundlefeaturedlabel'", $presenter);
        self::assertStringContainsString("\$offer['featuredlabel'] = \$featuredlabel", $presenter);
    }

    public function test_offer_titles_use_non_truncating_fit_contract(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/showroom/offer.mustache');
        $javascript = file_get_contents(__DIR__ . '/../../../amd/src/showroom.js');
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertStringContainsString('data-showroom-fit-lines="1"', $template);
        self::assertStringContainsString('data-showroom-fit-lines="2"', $template);
        self::assertStringContainsString('fitOfferEditorialText', $javascript);
        self::assertStringContainsString('text-overflow: clip', $css);
    }
}
