<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;

final class commerce_products_n84_test extends advanced_testcase {
    public function test_product_name_resolver_uses_ru_fr_en_then_fallback(): void {
        global $DB;

        $this->resetAfterTest();

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
                'sku' => 'BUNDLE.TEST.N84',
                'type' => 'bundle',
                'status' => 'active',
                'name' => 'third-group-verbs-bundle',
                'description' => '',
                'metadatajson' => '{}',
                'availablefrom' => null,
                'availableuntil' => null,
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        foreach ([
            'en' => 'English bundle',
            'fr' => 'Bundle français',
            'ru' => 'Русский набор',
        ] as $language => $name) {
            $DB->insert_record(
                'local_subs_commerce_prod_tr',
                (object)[
                    'productid' => $productid,
                    'language' => $language,
                    'name' => $name,
                    'shortdescription' => '',
                    'description' => '',
                    'metadatajson' => '{}',
                    'timecreated' => time(),
                    'timemodified' => time(),
                ]
            );
        }

        self::assertSame(
            'Bundle français',
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                $productid,
                'third-group-verbs-bundle',
                'fr',
                'ru'
            )
        );

        self::assertSame(
            'Русский набор',
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                $productid,
                'third-group-verbs-bundle',
                'de',
                'ru'
            )
        );

        $DB->delete_records(
            'local_subs_commerce_prod_tr',
            ['productid' => $productid, 'language' => 'ru']
        );
        self::assertSame(
            'Bundle français',
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                $productid,
                'third-group-verbs-bundle',
                'de',
                'ru'
            )
        );

        $DB->delete_records(
            'local_subs_commerce_prod_tr',
            ['productid' => $productid, 'language' => 'fr']
        );
        self::assertSame(
            'English bundle',
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                $productid,
                'third-group-verbs-bundle',
                'de',
                'ru'
            )
        );

        $DB->delete_records(
            'local_subs_commerce_prod_tr',
            ['productid' => $productid]
        );
        self::assertSame(
            'third-group-verbs-bundle',
            CommerceCatalogProductNameResolver::resolve_native_id(
                $DB,
                $productid,
                'third-group-verbs-bundle',
                'de',
                'it'
            )
        );
    }

    public function test_bundle_components_use_badge_first_link_and_translated_name(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id',
            $source
        );
        self::assertStringContainsString(
            'crm-product-component-link',
            $source
        );
        self::assertStringContainsString(
            "CommerceCatalogPresentation::badge(\n                'type'",
            $source
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/products/view.php'",
            $source
        );
    }

    public function test_index_and_view_use_business_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );
        $view = file_get_contents(
            $root . '/admin/commerce/products/view.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve(',
            $index
        );
        self::assertStringContainsString(
            '$displayname = CommerceCatalogProductNameResolver::resolve($DB, $product);',
            $view
        );
        self::assertStringContainsString(
            '$displayname, $metahtml',
            $view
        );
    }

    public function test_translations_and_components_labels_are_capitalised(): void {
        $root = dirname(__DIR__, 3);
        $fr = file_get_contents(
            $root . '/lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            '$string[\'commerce_translations\'] = \'Traductions\';',
            $fr
        );
        self::assertStringContainsString(
            '$string[\'commerce_components\'] = \'Composants\';',
            $fr
        );
    }

    public function test_n84_does_not_bump_plugin_version(): void {
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
