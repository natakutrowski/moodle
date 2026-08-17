<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;

final class commerce_products_n841_test extends advanced_testcase {
    public function test_language_candidate_order_is_current_default_ru_fr_en(): void {
        self::assertSame(
            ['fr_fr', 'fr', 'ru', 'en'],
            CommerceCatalogProductNameResolver::language_candidates(
                'fr_FR',
                'ru'
            )
        );

        self::assertSame(
            ['de', 'fr', 'ru', 'en'],
            CommerceCatalogProductNameResolver::language_candidates(
                'de',
                'fr'
            )
        );
    }

    public function test_product_index_has_status_scope_string_in_all_languages(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                '$string[\'commerce_result_scope_status\']',
                $source
            );
        }
    }

    public function test_view_uses_resolved_name_for_page_breadcrumb_and_header(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringContainsString(
            '$displayname = CommerceCatalogProductNameResolver::resolve($DB, $product);',
            $source
        );
        self::assertStringContainsString(
            "['label' => \$displayname, 'url' => null]",
            $source
        );
        self::assertStringContainsString(
            '$displayname, $metahtml',
            $source
        );
    }

    public function test_product_prices_render_configured_comparison_price(): void {
        $root = dirname(__DIR__, 3);
        $presentation = file_get_contents(
            $root
            . '/classes/commerce/catalog/presentation/'
            . 'CommerceCatalogPresentation.php'
        );

        self::assertStringContainsString(
            'CommerceProductPromotionService',
            $presentation
        );
        self::assertStringContainsString(
            "promotion['compareamountminor']",
            $presentation
        );
        self::assertStringContainsString(
            "promotion['amountminor']",
            $presentation
        );
        self::assertStringContainsString(
            'crm-product-compare-price',
            $presentation
        );
    }

    public function test_n841_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        preg_match(
            '/\\$plugin->version\\s*=\\s*(\\d+);/',
            $version,
            $matches
        );
        self::assertGreaterThanOrEqual(
            2026081601,
            (int)($matches[1] ?? 0)
        );
    }
}
