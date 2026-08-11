<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use moodle_url;

final class commerce_design_system_renderer_test extends advanced_testcase {
    public function test_empty_state_is_accessible_and_escapes_content(): void {
        $this->resetAfterTest();
        $html = CommerceDesignSystemRenderer::empty_state(
            '<Products>',
            '<No products>',
            new moodle_url('/local/subscriptions/admin/commerce/products/edit.php'),
            '<Add>'
        );

        $this->assertStringContainsString('crm-commerce-empty-state', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringNotContainsString('<Products>', $html);
        $this->assertStringNotContainsString('<No products>', $html);
    }

    public function test_product_header_has_single_h1_and_action_region(): void {
        $this->resetAfterTest();
        $html = CommerceProductPageHeaderRenderer::render(
            'A1 Full',
            '<span>Bundle</span>',
            '<a href="#">Edit</a>',
            'Product'
        );

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('crm-commerce-page-header-actions', $html);
        $this->assertStringContainsString('crm-commerce-eyebrow', $html);
    }
}
