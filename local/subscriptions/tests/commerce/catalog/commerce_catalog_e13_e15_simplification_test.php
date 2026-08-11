<?php

namespace local_subscriptions\commerce\catalog;

use advanced_testcase;
use local_subscriptions\commerce\catalog\editing\CommerceProductEditorCapabilities;

final class commerce_catalog_e13_e15_simplification_test extends advanced_testcase {
    public function test_fulfillments_are_not_catalog_editor_capabilities(): void {
        $this->assertFalse((new CommerceProductEditorCapabilities('course_access'))->can_edit_fulfillments());
        $this->assertFalse((new CommerceProductEditorCapabilities('digital_download'))->can_edit_fulfillments());
        $this->assertFalse((new CommerceProductEditorCapabilities('bundle'))->can_edit_fulfillments());
    }

    public function test_bundle_uses_its_specific_pricing_editor(): void {
        $capabilities = new CommerceProductEditorCapabilities('bundle');
        $this->assertFalse($capabilities->can_edit_prices());
        $this->assertTrue($capabilities->can_edit_components());
        $this->assertTrue($capabilities->can_preview_bundle());
    }
}
