<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

final class commerce_795f6h_legacy_visibility_and_shared_badges_test extends \advanced_testcase {
    public function test_catalogue_displays_origin_next_to_sku(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/index.php');
        self::assertIsString($source);
        self::assertStringContainsString('commerce_catalog_origin_native_short', $source);
        self::assertStringContainsString('commerce_catalog_origin_legacy_short', $source);
        self::assertStringContainsString("optional_param('origin'", $source);
    }

    public function test_badges_use_shared_partial_through_the_final_renderer_chain(): void {
        $root = dirname(__DIR__, 3);
        $partial = $root . '/templates/storefront/product_badges.mustache';
        $panel = $root . '/templates/storefront/product_commerce_panel.mustache';
        $card = $root . '/templates/storefront/product_card.mustache';

        self::assertFileExists($partial);
        self::assertStringContainsString(
            'local_subscriptions/storefront/product_badges',
            (string)file_get_contents($card)
        );
        self::assertStringContainsString(
            'local_subscriptions/storefront/product_badges',
            (string)file_get_contents($panel)
        );

        foreach (['default', 'editorial', 'immersive'] as $name) {
            $source = (string)file_get_contents(
                $root . '/templates/storefront/product_templates/' . $name . '.mustache'
            );
            self::assertStringContainsString('commerce-product-type-badge', $source);
            self::assertStringContainsString(
                'local_subscriptions/storefront/product_commerce_panel',
                $source
            );
        }
    }

    public function test_gustave_medallion_uses_final_independent_size(): void {
        $css = (string)file_get_contents(dirname(__DIR__, 3) . '/styles/storefront.css');
        self::assertStringContainsString('width: 4.0rem;', $css);
        self::assertStringContainsString('height: 4.0rem;', $css);
        self::assertStringContainsString('padding: .45rem 1rem .45rem 4.85rem;', $css);
    }
}
