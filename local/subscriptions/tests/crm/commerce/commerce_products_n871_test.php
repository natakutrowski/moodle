<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n871_test extends advanced_testcase {
    public function test_new_price_card_renderer_accepts_no_existing_price(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/prices.php'
        );

        self::assertStringContainsString(
            '?CommerceProductPrice $price = null',
            $source
        );
        self::assertStringContainsString(
            'echo $renderprice();',
            $source
        );
    }

    public function test_n87_keeps_schema_version_independent_from_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $install = file_get_contents($root . '/db/install.xml');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
        self::assertStringContainsString(
            'VERSION="2026081510"',
            $install
        );
    }

    public function test_catalogue_price_presentation_uses_canonical_promotion_service(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/catalog/presentation/'
            . 'CommerceCatalogPresentation.php'
        );

        self::assertStringContainsString(
            'CommerceProductPromotionService',
            $source
        );
        self::assertStringContainsString(
            "promotion['amountminor']",
            $source
        );
        self::assertStringContainsString(
            "promotion['compareamountminor']",
            $source
        );
    }
}
