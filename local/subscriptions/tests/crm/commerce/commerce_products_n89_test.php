<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_products_n89_test extends advanced_testcase {
    public function test_bundle_composition_starts_with_two_rows(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        self::assertStringContainsString(
            "optional_param('rows', 2, PARAM_INT)",
            $source
        );
        self::assertStringContainsString(
            'max($rowcount, count($current), 2)',
            $source
        );
    }

    public function test_bundle_composition_uses_product_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        foreach ([
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            '$displayname,',
            '$candidatename = CommerceCatalogProductNameResolver::resolve_native_id(',
            '$expandedname =',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            '$candidate->get_name() . \' — \'',
            $source
        );
    }

    public function test_bundle_composition_supports_drag_drop_and_arrows(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        foreach ([
            'draggable',
            'data-move',
            'data-sortorder',
            'dragstart',
            'dragover',
            'drop',
            'fa fa-chevron-up',
            'fa fa-chevron-down',
            'fa fa-bars',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            "get_string('commerce_bundle_component_order'",
            $source
        );
    }

    public function test_bundle_composition_adds_rows_client_side(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        self::assertStringContainsString(
            'bundle-component-template',
            $source
        );
        self::assertStringContainsString(
            'data-add-component',
            $source
        );
        self::assertStringContainsString(
            'cloneNode(true)',
            $source
        );
    }

    public function test_expanded_preview_uses_resolved_names_and_links(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/components.php'
        );

        self::assertStringContainsString(
            'crm-bundle-expanded-preview-name',
            $source
        );
        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/products/view.php',
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_product_sku'",
            $source
        );
    }

    public function test_n89_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
