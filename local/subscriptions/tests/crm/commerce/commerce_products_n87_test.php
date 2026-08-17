<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;

final class commerce_products_n87_test extends advanced_testcase {
    public function test_new_promotion_contract_changes_effective_price_only_inside_window(): void {
        $service = new CommerceProductPromotionService();
        $metadata = $service->with_promotion(
            [],
            'EUR',
            3900,
            1000,
            2000
        );

        self::assertNull(
            $service->resolve($metadata, 'EUR', 5400, 999)
        );

        $active = $service->resolve(
            $metadata,
            'EUR',
            5400,
            1500
        );
        self::assertNotNull($active);
        self::assertSame(3900, $active['amountminor']);
        self::assertSame(5400, $active['compareamountminor']);
        self::assertSame(28, $active['discountpercentage']);

        self::assertNull(
            $service->resolve($metadata, 'EUR', 5400, 2001)
        );
    }

    public function test_pricing_page_explains_automatic_restore_and_uses_resolved_breadcrumb(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/prices.php'
        );

        foreach ([
            'commerce_product_pricing_regular_price',
            'commerce_product_promotion_price',
            'commerce_product_promotion_auto_restore',
            'promotion_enabled',
            'promotion_start',
            'promotion_end',
            'CommerceCatalogProductNameResolver::resolve_native_id',
            'CommerceProductEditorNavigationRenderer::breadcrumb(',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            '    $displayname,',
            $source
        );
    }

    public function test_cart_gateway_applies_product_promotion_to_authoritative_quote(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/cart/catalog/'
            . 'MoodleCommerceCartCatalogGateway.php'
        );

        self::assertStringContainsString(
            'CommerceProductPromotionService',
            $source
        );
        self::assertStringContainsString(
            "\$promotion['amountminor'] ?? \$price->get_amount_minor()",
            $source
        );
    }

    public function test_storefront_projects_promotion_as_sale_price_and_regular_compare_price(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/storefront/repository/'
            . 'CommerceStorefrontRepository.php'
        );

        self::assertStringContainsString(
            "\$promotion['amountminor'] ?? \$price->get_amount_minor()",
            $source
        );
        self::assertStringContainsString(
            "\$promotion['compareamountminor'] ?? null",
            $source
        );
    }

    public function test_storefront_editor_no_longer_renders_promotion_inputs(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/storefront.php'
        );

        self::assertStringNotContainsString(
            'commerce_storefront_promotions_title',
            $source
        );
        self::assertStringNotContainsString(
            "name' => 'promotion_",
            $source
        );
    }

    public function test_upgrade_migrates_legacy_compare_price_contract(): void {
        $root = dirname(__DIR__, 3);
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString(
            'if ($oldversion < 2026081602)',
            $upgrade
        );
        self::assertStringContainsString(
            "'saleamountminor' => \$saleamountminor",
            $upgrade
        );
        self::assertStringContainsString(
            '$price->amountminor = $compareminor;',
            $upgrade
        );
    }

    public function test_n87_bumps_version_for_data_migration(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
