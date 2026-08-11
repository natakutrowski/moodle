<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_catalog_e19b_plans_scopes_test extends \advanced_testcase {
    public function test_plan_toggle_is_posted_and_reused(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents($root . '/classes/commerce/catalog/presentation/CommercePlanStatusToggleRenderer.php');
        $index = file_get_contents($root . '/admin/commerce/plans/index.php');
        $view = file_get_contents($root . '/admin/commerce/plans/view.php');
        $toggle = file_get_contents($root . '/admin/commerce/plans/toggle.php');

        self::assertStringContainsString("'method' => 'post'", $renderer);
        self::assertStringContainsString('CommercePlanStatusToggleRenderer::render', $index);
        self::assertStringContainsString('CommercePlanStatusToggleRenderer::render', $view);
        self::assertStringContainsString("REQUEST_METHOD'] !== 'POST'", $toggle);
    }

    public function test_plan_and_scope_views_show_technical_dates(): void {
        $root = dirname(__DIR__, 3);
        foreach (['plans/view.php', 'accessscopes/view.php'] as $relative) {
            $content = file_get_contents($root . '/admin/commerce/' . $relative);
            self::assertStringContainsString('commerce_date_created', $content);
            self::assertStringContainsString('commerce_date_modified', $content);
            self::assertStringContainsString('userdate(', $content);
        }
    }

    public function test_cover_errors_are_converted_to_notifications(): void {
        $root = dirname(__DIR__, 3);
        $content = file_get_contents($root . '/admin/commerce/products/assets.php');
        self::assertStringContainsString('NOTIFY_ERROR', $content);
        self::assertStringContainsString("'maxbytes'", $content);
        self::assertStringContainsString('CommerceCatalogDigitalFileManager::MAX_BYTES', $content);
    }

    public function test_product_editor_has_return_to_product_view(): void {
        $root = dirname(__DIR__, 3);
        $content = file_get_contents($root . '/classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php');
        self::assertStringContainsString("products/view.php", $content);
        self::assertStringContainsString('commerce_product_back_to_view', $content);
    }
}
