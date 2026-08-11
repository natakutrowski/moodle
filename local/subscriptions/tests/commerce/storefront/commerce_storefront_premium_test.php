<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

use advanced_testcase;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema;

final class commerce_storefront_premium_test extends advanced_testcase {
    public function test_premium_section_types_and_styles_are_controlled(): void {
        $this->assertContains('timeline', CommerceStorefrontPageEditor::section_types());
        $this->assertContains('comparison', CommerceStorefrontPageEditor::section_types());
        $this->assertContains('accordion', CommerceStorefrontPageEditor::section_types());
        $this->assertContains('glass', CommerceStorefrontSectionSchema::STYLES);
        $this->assertContains('gradient', CommerceStorefrontSectionSchema::STYLES);
        $this->assertContains('minimal', CommerceStorefrontSectionSchema::STYLES);
    }

    public function test_invalid_premium_values_fall_back_safely(): void {
        $section = CommerceStorefrontSectionSchema::normalise([
            'type' => 'timeline',
            'style' => 'javascript',
            'id' => 'premium-section',
        ], 0);

        $this->assertNotNull($section);
        $this->assertSame('default', $section['style']);
    }

    public function test_public_template_contains_accessible_premium_components(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/storefront/product_section.mustache');
        $this->assertIsString($template);
        $this->assertStringContainsString('commerce-premium-timeline', $template);
        $this->assertStringContainsString('<details>', $template);
        $this->assertStringContainsString('data-premium-animation', $template);
    }
}
