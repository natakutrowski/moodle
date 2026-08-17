<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n810_test extends advanced_testcase {
    public function test_bundle_pricing_uses_resolved_product_name_and_no_duplicate_preview_button(): void {
        $root = dirname(__DIR__, 3);
        $pricing = file_get_contents(
            $root . '/admin/commerce/products/pricing.php'
        );
        $components = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $pricing
        );
        self::assertStringContainsString(
            '    $displayname,',
            $pricing
        );
        self::assertStringNotContainsString(
            'commerce_bundle_open_preview',
            $pricing
        );
        self::assertStringNotContainsString(
            'commerce_bundle_open_preview',
            $components
        );
    }

    public function test_bundle_pricing_has_three_clear_strategy_options(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/pricing.php'
        );

        foreach ([
            'CommerceBundlePricingStrategy::FIXED',
            'CommerceBundlePricingStrategy::COMPONENT_SUM',
            'CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT',
            'crm-bundle-pricing-strategy-option',
            'data-strategy-option',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_bundle_pricing_uses_canonical_promotion_service_per_currency(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/pricing.php'
        );

        foreach ([
            'CommerceProductPromotionService',
            'promotion_',
            '_enabled',
            '_amount',
            '_start',
            '_end',
            'commerce_product_promotion_auto_restore',
            'commerce_product_promotion_timezone',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            '$promotionservice->with_promotion(',
            $source
        );
        self::assertStringContainsString(
            '$promotionservice->without_promotion(',
            $source
        );
    }

    public function test_bundle_pricing_reloads_product_after_rebuilding_prices_before_saving_promotions(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/pricing.php'
        );

        self::assertStringContainsString(
            '$pricing->configure(',
            $source
        );
        self::assertStringContainsString(
            '$freshproduct = $manager',
            $source
        );
        self::assertStringContainsString(
            '$manager->save_metadata($sku, $metadata);',
            $source
        );
    }

    public function test_n810_keeps_existing_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
