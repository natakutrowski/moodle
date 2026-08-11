<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\editing\CommerceCatalogCompatibilityEditor;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;

final class commerce_catalog_e7_e10_editor_test extends advanced_testcase {
    public function test_course_access_capabilities_keep_plan_and_scope_separate(): void {
        $product = new CommerceProduct('PLAN.A1', CommerceProductType::COURSE_ACCESS, 'active', 'A1');
        $capabilities = CommerceProductEditorCapabilities::for_product($product);

        $this->assertTrue($capabilities->can_edit_identity());
        $this->assertTrue($capabilities->can_edit_prices());
        $this->assertFalse($capabilities->can_edit_fulfillments());
        $this->assertTrue($capabilities->can_manage_access_scope());
        $this->assertFalse($capabilities->can_edit_components());
        $this->assertFalse($capabilities->can_preview_bundle());
    }

    public function test_bundle_keeps_only_its_specialised_sections(): void {
        $product = new CommerceProduct('BUNDLE.A1', CommerceProductType::BUNDLE, 'draft', 'Bundle');
        $capabilities = CommerceProductEditorCapabilities::for_product($product);

        $this->assertFalse($capabilities->can_edit_prices());
        $this->assertFalse($capabilities->can_edit_fulfillments());
        $this->assertFalse($capabilities->can_manage_access_scope());
        $this->assertTrue($capabilities->can_edit_components());
        $this->assertTrue($capabilities->can_preview_bundle());
    }

    public function test_legacy_records_remain_edited_in_their_configured_source_screen(): void {
        $editor = new CommerceCatalogCompatibilityEditor();
        $this->assertTrue($editor->is_native_editable('native'));
        $this->assertFalse($editor->is_native_editable('legacy_plan'));
        $this->assertStringContainsString(subscription_config::commerce_plan_edit_page(), $editor->legacy_edit_url('legacy_plan', 7)->out(false));
        $this->assertStringContainsString(subscription_config::digital_product_edit_admin_page(), $editor->legacy_edit_url('legacy_digital', 3)->out(false));
    }
}
