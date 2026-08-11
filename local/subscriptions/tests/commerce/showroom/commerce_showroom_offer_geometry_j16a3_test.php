<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

/** Contracts for J16A.3 canonical offer-card geometry. */
final class commerce_showroom_offer_geometry_j16a3_test extends \advanced_testcase {
    public function test_empty_bundle_catalogue_description_is_not_rendered(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/showroom/offer.mustache');
        $presenter = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );

        self::assertIsString($template);
        self::assertIsString($presenter);
        self::assertStringContainsString('{{#hasdescription}}', $template);
        self::assertStringContainsString("\$offer['hasdescription']", $presenter);
    }

    public function test_featured_bundle_is_not_scaled_and_all_roles_keep_builder_casing(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('J16A.3 — canonical offer-card geometry', $css);
        self::assertStringContainsString('transform: none !important;', $css);
        self::assertStringContainsString('text-transform: none;', $css);
        self::assertStringContainsString('flex: 0 0 auto;', $css);
    }

    public function test_bundle_fallback_uses_lowercase_editorial_role(): void {
        $defaults = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/cms/CommerceShowroomBlockDefaultsCatalog.php'
        );

        self::assertIsString($defaults);
        self::assertStringContainsString("'ru' => 'полное восхождение'", $defaults);
        self::assertStringContainsString("'en' => 'complete ascent'", $defaults);
        self::assertStringContainsString("default => 'ascension complète'", $defaults);
    }
}
