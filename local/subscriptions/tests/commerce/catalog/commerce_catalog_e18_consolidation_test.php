<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator;

final class commerce_catalog_e18_consolidation_test extends advanced_testcase {
    public function test_admin_links_are_centralised_in_subscription_config(): void {
        $this->assertStringContainsString(subscription_config::commerce_plan_edit_page(), CommerceLegacyCatalogLinkGenerator::plan_edit_url(1)->out(false));
        $this->assertStringContainsString(subscription_config::commerce_access_scope_edit_page(), CommerceLegacyCatalogLinkGenerator::scope_edit_url(1)->out(false));
    }

    public function test_media_manager_does_not_depend_on_undefined_file_internal_constant(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');
        $this->assertStringNotContainsString('FILE_INTERNAL', $source);
    }

    public function test_catalogue_price_editor_does_not_expose_provider_fields(): void {
        $source = file_get_contents(dirname(__DIR__, 3) . '/admin/commerce/products/prices.php');
        $this->assertStringNotContainsString("name' => 'provider'", $source);
        $this->assertStringNotContainsString("name' => 'providerpriceid'", $source);
    }
}
