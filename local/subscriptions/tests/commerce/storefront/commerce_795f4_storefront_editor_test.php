<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;

final class commerce_795f4_storefront_editor_test extends advanced_testcase {
    public function test_editor_merges_storefront_configuration_without_losing_metadata(): void {
        $editor = new CommerceStorefrontPageEditor();
        $metadata = ['cover' => 'cover.png', 'custom' => ['kept' => true]];
        $submitted = [
            'template' => 'editorial',
            'theme' => 'a1-premium',
            'section_type_0' => 'features',
            'section_title_0' => 'Benefits',
            'section_items_0' => "Structured course ||| Learn step by step\nPractice ||| Interactive exercises",
            'section_type_1' => 'faq',
            'section_items_1' => 'How long? ||| Lifetime access',
        ];

        $result = $editor->merge_submission($metadata, $submitted, 'fr');

        $this->assertSame('cover.png', $result['cover']);
        $this->assertTrue($result['custom']['kept']);
        $this->assertSame('editorial', $result['storefront']['template']);
        $this->assertSame('a1-premium', $result['storefront']['theme']);
        $this->assertCount(2, $result['storefront']['sections']);
        $this->assertSame('Structured course', $result['storefront']['sections'][0]['items'][0]['title']);
        $this->assertSame('Lifetime access', $result['storefront']['sections'][1]['items'][0]['answer']);
    }

    public function test_editor_reads_legacy_storefront_metadata_and_normalises_invalid_values(): void {
        $editor = new CommerceStorefrontPageEditor();
        $product = new CommerceProduct(
            'COURSE-A1',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1',
            '',
            [
                'storefront_template' => 'immersive',
                'storefront_theme' => 'Bad theme!',
                'storefront_sections' => [[
                    'type' => 'testimonial',
                    'quote' => 'Excellent',
                    'author' => 'Student',
                ]],
            ]
        );

        $definition = $editor->definition_from_product($product);
        $rows = $editor->form_rows($product);

        $this->assertSame('immersive', $definition['template']);
        $this->assertSame('default', $definition['theme']);
        $this->assertSame('testimonial', $rows[0]['type']);
        $this->assertSame('Excellent', $rows[0]['content']);
        $this->assertSame('Student', $rows[0]['auxiliary']);
        $this->assertCount(CommerceStorefrontPageEditor::MAX_SECTIONS, $rows);
    }
}
