<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontComposerTemplateService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontVisualBuilderService;

final class commerce_storefront_composer_templates_test extends advanced_testcase {
    public function test_templates_use_the_controlled_layout_contract(): void {
        $service = new CommerceStorefrontComposerTemplateService();

        foreach (CommerceStorefrontComposerTemplateService::TEMPLATES as $template) {
            $sections = $service->sections($template, 2);
            $this->assertNotEmpty($sections);
            foreach ($sections as $position => $section) {
                $this->assertSame(20 + ($position * 10), $section['order']);
                $this->assertSame('section-' . ($position + 3), $section['id']);
                $this->assertArrayHasKey('layout', $section);
                $this->assertContains($section['layout']['columns'], [1, 2, 3]);
            }
        }
    }

    public function test_template_is_appended_without_replacing_existing_sections(): void {
        $metadata = [
            'storefront' => [
                'locales' => [
                    'fr' => [
                        'sections' => [[
                            'id' => 'existing',
                            'type' => 'rich_text',
                            'visible' => true,
                            'order' => 0,
                        ]],
                    ],
                ],
            ],
        ];

        $updated = (new CommerceStorefrontVisualBuilderService())->apply(
            $metadata,
            'fr',
            'apply_template',
            '',
            'sales'
        );

        $sections = $updated['storefront']['locales']['fr']['sections'];
        $this->assertSame('existing', $sections[0]['id']);
        $this->assertGreaterThan(1, count($sections));
    }

    public function test_unknown_template_does_not_change_the_page(): void {
        $metadata = ['storefront' => ['locales' => ['fr' => ['sections' => []]]]];
        $updated = (new CommerceStorefrontVisualBuilderService())->apply(
            $metadata,
            'fr',
            'apply_template',
            '',
            'unknown'
        );
        $this->assertSame([], $updated['storefront']['locales']['fr']['sections']);
    }
}
