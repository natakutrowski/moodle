<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontComposerTemplateService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;

final class commerce_storefront_builder_first_l4_test extends advanced_testcase {
    public function test_commerce_panel_can_be_hidden_without_disabling_storefront(): void {
        self::assertContains(
            CommerceStorefrontLayoutContract::NONE,
            CommerceStorefrontLayoutContract::commerce_positions()
        );
        self::assertSame(
            CommerceStorefrontLayoutContract::NONE,
            CommerceStorefrontLayoutContract::normalise_commerce_position(
                'none',
                CommerceStorefrontLayoutContract::DIGITAL
            )
        );
    }

    public function test_editor_persists_builder_first_contract(): void {
        $editor = new CommerceStorefrontPageEditor();
        $metadata = $editor->merge_submission([], [
            'template' => 'default',
            'commerce_position' => 'none',
            'product_header_mode' => 'builder',
            'shell_mode' => 'standard',
            'show_header' => 1,
            'show_footer' => 1,
            'global_zones' => ['hero', 'commerce', 'content', 'recommendations'],
            'theme' => 'default',
            'section_type_0' => 'hero',
            'section_visible_0' => 1,
            'section_order_0' => 0,
            'section_title_0' => 'Digital cards',
            'section_content_0' => '<p>Intro</p>',
            'section_content_itemid_0' => 123,
            'section_auxiliary_0' => '',
            'section_alt_0' => 'Cards',
        ], 'fr');

        self::assertSame('none', $metadata['storefront']['commerce_position']);
        self::assertSame('builder', $metadata['storefront']['product_header_mode']);
        self::assertSame('hero', $metadata['storefront']['sections'][0]['type']);
        self::assertSame(123, $metadata['storefront']['sections'][0]['mediaitemid']);
    }

    public function test_digital_preset_is_builder_first_and_complete(): void {
        $sections = (new CommerceStorefrontComposerTemplateService())
            ->sections('digital');
        $types = array_column($sections, 'type');

        self::assertSame('hero', $types[0]);
        self::assertContains('features', $types);
        self::assertSame(2, count(array_filter(
            $types,
            static fn(string $type): bool => $type === 'image_text'
        )));
        self::assertContains('gallery', $types);
        self::assertContains('faq', $types);
        self::assertSame('cta', $types[array_key_last($types)]);
    }

    public function test_cta_uses_native_cart_contract_instead_of_legacy_link(): void {
        global $CFG;

        $template = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_section.mustache'
        );

        self::assertStringContainsString('method="post" action="{{cartaction}}"', $template);
        self::assertStringContainsString('name="priceid" value="{{id}}"', $template);
        self::assertStringContainsString('name="sesskey" value="{{cartsesskey}}"', $template);
        self::assertStringNotContainsString(
            '<a href="{{quickpurchaseurl}}" class="btn btn-primary btn-lg">',
            $template
        );
    }

    public function test_every_product_layout_can_suppress_automatic_product_header(): void {
        global $CFG;

        foreach (CommerceStorefrontLayoutContract::layouts() as $layout) {
            $path = $CFG->dirroot
                . '/local/subscriptions/templates/storefront/product_templates/'
                . $layout
                . '.mustache';
            $template = (string)file_get_contents($path);
            self::assertStringContainsString('{{#showproducthero}}', $template, $layout);
            self::assertStringContainsString('{{/showproducthero}}', $template, $layout);
        }
    }

    public function test_empty_preset_sections_are_not_rendered_publicly(): void {
        global $CFG;

        $presenter = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        self::assertStringContainsString(
            "static fn(array \$section): bool => !empty(\$section['renderable'])",
            $presenter
        );
        self::assertStringContainsString(
            'private function is_renderable_section(array $section): bool',
            $presenter
        );
    }

    public function test_builder_hero_falls_back_to_product_editorial_content(): void {
        global $CFG;

        $presenter = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        self::assertStringContainsString(
            '$product->get_name()',
            $presenter
        );
        self::assertStringContainsString(
            '$product->get_short_description()',
            $presenter
        );
        self::assertStringContainsString(
            "if (trim((string)(\$section['title'] ?? '')) === '')",
            $presenter
        );
    }

    public function test_hero_media_upload_and_fallback_are_part_of_runtime_contract(): void {
        global $CFG;

        $admin = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );
        $presenter = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        self::assertStringContainsString(
            "['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features']",
            $admin
        );
        self::assertStringContainsString("['hero', 'image_text']", $admin);
        self::assertStringContainsString('private function present_hero(', $presenter);
        self::assertStringContainsString("foreach (['product', 'storefront', 'showroom'] as " . '$context' . ')', $presenter);
        self::assertStringContainsString('CommerceShowroomMediaService::create()->definition(', $presenter);
    }
    public function test_hero_internal_layout_settings_are_persisted_and_rendered(): void {
        global $CFG;

        $editor = new CommerceStorefrontPageEditor();
        $metadata = $editor->merge_submission([], [
            'template' => 'default',
            'commerce_position' => 'none',
            'product_header_mode' => 'builder',
            'section_type_0' => 'hero',
            'section_visible_0' => 1,
            'section_order_0' => 0,
            'section_hero_layout_0' => 'media_text',
            'section_hero_ratio_0' => '45_55',
            'section_hero_media_ratio_0' => '1_1',
        ], 'fr');

        $hero = $metadata['storefront']['sections'][0];
        self::assertSame('media_text', $hero['herolayout']);
        self::assertSame('45_55', $hero['heroratio']);
        self::assertSame('1_1', $hero['heromediaratio']);

        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache'
        );
        self::assertStringContainsString('commerce-editorial-hero {{heroclasses}}', $template);

        $admin = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );
        self::assertStringContainsString('section_hero_layout_', $admin);
        self::assertStringContainsString('section_hero_ratio_', $admin);
        self::assertStringContainsString('section_hero_media_ratio_', $admin);
    }

}
