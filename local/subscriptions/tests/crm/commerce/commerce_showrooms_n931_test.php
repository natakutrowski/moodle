<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showrooms_n931_test extends advanced_testcase {
    public function test_information_page_separates_configuration_products_and_publication(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce_showroom_n931_general_title',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_config_products',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_n931_publication_title',
            $source
        );
        self::assertStringContainsString(
            'commerce-showroom-information-advanced',
            $source
        );
    }

    public function test_information_header_keeps_preview_and_context_menu_only(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce-showroom-information-actions-menu',
            $source
        );
        self::assertStringContainsString(
            'commerce_showroom_builder_preview',
            $source
        );

        $headerstart = strpos(
            $source,
            'commerce-showroom-information-header'
        );
        $subnav = strpos(
            $source,
            '$tabs = ['
        );
        $header = substr(
            $source,
            $headerstart,
            $subnav - $headerstart
        );

        self::assertStringNotContainsString(
            'commerce_showroom_history',
            $header
        );
        self::assertStringNotContainsString(
            'workflownote',
            $header
        );
    }

    public function test_product_selector_uses_business_name_resolver_without_sku_suffix(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/showroom/cms/CommerceShowroomProductLinkOptions.php'
        );

        self::assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $source
        );
        self::assertStringNotContainsString(
            ". ' — ' . \$product->get_sku()",
            $source
        );
    }

    public function test_information_save_button_is_right_aligned(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/showrooms/edit.php'
        );

        self::assertStringContainsString(
            'commerce-showroom-information-save',
            $source
        );
    }

    public function test_n931_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
