<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_editorial_builder_test
        extends \advanced_testcase {

    public function test_schema_supports_advanced_controlled_sections(): void {
        foreach ([
            'hero',
            'rich_text',
            'image_text',
            'video',
            'features',
            'program',
            'instructor',
            'testimonials',
            'faq',
            'gallery',
            'cta',
        ] as $type) {
            $this->assertContains(
                $type,
                CommerceStorefrontSectionSchema::TYPES
            );
        }
        $this->assertSame(3, CommerceStorefrontSectionSchema::VERSION);
    }

    public function test_hidden_sections_are_not_rendered_and_order_is_stable(): void {
        $sections = CommerceStorefrontSectionSchema::sort_visible([
            [
                'id' => 'second',
                'type' => 'rich_text',
                'visible' => true,
                'order' => 20,
            ],
            [
                'id' => 'hidden',
                'type' => 'faq',
                'visible' => false,
                'order' => 5,
            ],
            [
                'id' => 'first',
                'type' => 'hero',
                'visible' => true,
                'order' => 10,
            ],
        ]);

        $this->assertCount(2, $sections);
        $this->assertSame('first', $sections[0]['id']);
        $this->assertSame('second', $sections[1]['id']);
    }

    public function test_template_contains_responsive_video_and_new_sections(): void {
        global $CFG;

        $collection = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_sections.mustache'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_section.mustache'
        );

        $this->assertStringContainsString(
            'local_subscriptions/storefront/product_section',
            $collection
        );
        $this->assertStringContainsString('{{#ishero}}', $template);
        $this->assertStringContainsString('{{#isimagetext}}', $template);
        $this->assertStringContainsString('{{#isvideo}}', $template);
        $this->assertStringContainsString('{{#isprogram}}', $template);
        $this->assertStringContainsString('{{#isinstructor}}', $template);
        $this->assertStringContainsString('{{#isgallery}}', $template);
        $this->assertStringContainsString('{{{videohtml}}}', $template);
    }

    public function test_reusable_commerce_panel_and_editorial_cta_share_conversion_contract(): void {
        global $CFG;

        $sections = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_section.mustache'
        );
        $panel = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_commerce_panel.mustache'
        );

        foreach ([
            'cartaction',
            'toggleaction',
            'buy_now_label',
        ] as $commercecontract) {
            self::assertStringContainsString($commercecontract, $panel);
            self::assertStringContainsString($commercecontract, $sections);
        }

        self::assertStringContainsString(
            '{{> local_subscriptions/storefront/product_price }}',
            $panel
        );
        self::assertStringContainsString(
            '{{> local_subscriptions/storefront/product_price }}',
            $sections
        );
    }
}
