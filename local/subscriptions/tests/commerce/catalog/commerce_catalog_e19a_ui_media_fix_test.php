<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_catalog_e19a_ui_media_fix_test extends advanced_testcase {
    public function test_media_manager_does_not_reference_file_internal(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');

        $this->assertStringNotContainsString('FILE_INTERNAL', $source);
        $this->assertStringContainsString("'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']", $source);
        $this->assertStringContainsString('public const MAX_BYTES = 10 * 1024 * 1024;', $source);
        $this->assertStringContainsString("'maxbytes' => self::MAX_BYTES", $source);
    }

    public function test_product_action_bar_adds_explicit_button_spacing(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/classes/crm/commerce/presentation/CommerceDesignSystemRenderer.php');

        $this->assertStringContainsString("' mb-2'", $source);
        $this->assertStringContainsString('buttonclass', $source);
        $this->assertStringContainsString('me-2', $source);
    }

    public function test_price_active_checkbox_uses_a_labelled_form_check(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/admin/commerce/products/prices.php');

        $this->assertStringContainsString('form-check-input', $source);
        $this->assertStringContainsString('form-check-label', $source);
    }

    public function test_access_scope_actions_use_the_centralised_link_generator(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/admin/commerce/products/access_scope.php');

        $this->assertStringContainsString('CommerceLegacyCatalogLinkGenerator::plan_edit_url', $source);
        $this->assertStringContainsString('CommerceLegacyCatalogLinkGenerator::scope_edit_url', $source);
        $this->assertStringNotContainsString('/admin/plans/edit.php', $source);
        $this->assertStringNotContainsString('/admin/scopes/edit.php', $source);
    }

    public function test_plan_editor_renders_the_commerce_navigation_once(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/admin/commerce/plans/edit.php');

        $this->assertSame(1, substr_count(
            $source,
            'CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context)'
        ));
    }
}
