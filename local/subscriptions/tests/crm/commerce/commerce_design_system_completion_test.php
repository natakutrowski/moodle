<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;

final class commerce_design_system_completion_test extends advanced_testcase {
    public function test_page_intro_escapes_content(): void {
        $html = CommerceDesignSystemRenderer::page_intro('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_notice_has_accessible_role(): void {
        $html = CommerceDesignSystemRenderer::notice('Problem', 'Try again.', 'danger');
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('alert-danger', $html);
    }

    public function test_form_actions_has_shared_class(): void {
        $html = CommerceDesignSystemRenderer::form_actions('<button>Save</button>');
        $this->assertStringContainsString('crm-commerce-form-actions', $html);
        $this->assertStringContainsString('<button>Save</button>', $html);
    }

    public function test_section_heading_uses_one_section_heading(): void {
        $html = CommerceDesignSystemRenderer::section_heading('Pricing', 'Configure prices.');
        $this->assertSame(1, substr_count($html, '<h2'));
        $this->assertStringContainsString('crm-commerce-section-heading', $html);
    }
}
