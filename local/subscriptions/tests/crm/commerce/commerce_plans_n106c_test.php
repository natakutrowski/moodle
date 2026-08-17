<?php

namespace local_subscriptions;

final class commerce_plans_n106c_test extends \advanced_testcase {
    public function test_legacy_price_and_translation_pages_are_explicit_compatibility_screens(): void {
        $root = dirname(__DIR__, 3);
        foreach (['prices.php', 'translations.php'] as $file) {
            $source = file_get_contents($root . '/admin/plans/' . $file);
            $this->assertStringContainsString('CommerceSectionNavigationRenderer::PRODUCTS', $source, $file);
            $this->assertStringContainsString('local_subs_commerce_prod_map', $source, $file);
            $this->assertStringContainsString('commerce_plan_legacy_compatibility_title', $source, $file);
            $this->assertStringContainsString('commerce_plan_legacy_readonly_notice', $source, $file);
            $this->assertStringContainsString('subscription_plan', $source, $file);
        }
    }

    public function test_mapped_plan_prices_redirect_to_native_pricing_and_become_read_only(): void {
        $root = dirname(__DIR__, 3);
        $prices = file_get_contents($root . '/admin/plans/prices.php');
        $renderer = file_get_contents($root . '/renderer/plans_renderer.php');

        $this->assertStringContainsString("'/local/subscriptions/admin/commerce/products/prices.php'", $prices);
        $this->assertStringContainsString("['sku' => \$nativeproduct->sku]", $prices);
        $this->assertStringContainsString('render_prices_table($prices, $legacyreadonly)', $prices);
        $this->assertStringContainsString('bool $readonly = false', $renderer);
        $this->assertStringContainsString('if (!$readonly)', $renderer);
        $this->assertStringContainsString('commerce_plan_legacy_readonly_badge', $renderer);
    }

    public function test_mapped_plan_translations_redirect_to_native_editor_and_cannot_be_mutated(): void {
        $root = dirname(__DIR__, 3);
        $translations = file_get_contents($root . '/admin/plans/translations.php');
        $renderer = file_get_contents($root . '/renderer/plans_renderer.php');

        $this->assertStringContainsString("'/local/subscriptions/admin/commerce/products/edit.php'", $translations);
        $this->assertStringContainsString('$submittedplanid = required_param', $translations);
        $this->assertStringContainsString('$deleteplanid = (int)$DB->get_field', $translations);
        $this->assertStringContainsString('isset($nativeproducts[$submittedplanid])', $translations);
        $this->assertStringContainsString('isset($nativeproducts[$deleteplanid])', $translations);
        $this->assertStringContainsString('array $nativeproducts = []', $renderer);
        $this->assertStringContainsString('commerce_plan_open_native_translations', $renderer);
    }


    public function test_scope_translations_are_legacy_compatibility_and_read_only_when_native_products_exist(): void {
        $root = dirname(__DIR__, 3);
        $translations = file_get_contents($root . '/admin/scopes/translations.php');
        $renderer = file_get_contents($root . '/renderer/scopes_renderer.php');

        $this->assertStringContainsString('CommerceSectionNavigationRenderer::PRODUCTS', $translations);
        $this->assertStringContainsString('local_subs_commerce_prod_map', $translations);
        $this->assertStringContainsString("['legacytable' => 'subscription_plan']", $translations);
        $this->assertStringContainsString('commerce_scope_legacy_compatibility_title', $translations);
        $this->assertStringContainsString('$submittedscopeid = required_param', $translations);
        $this->assertStringContainsString('$deletescopeid = (int)$DB->get_field', $translations);
        $this->assertStringContainsString('!empty($readonlyscopes[$submittedscopeid])', $translations);
        $this->assertStringContainsString('!empty($readonlyscopes[$deletescopeid])', $translations);
        $this->assertStringContainsString('array $nativeproductsbyscope = [], array $readonlyscopes = []', $renderer);
        $this->assertStringContainsString('commerce_scope_legacy_readonly_badge', $renderer);
        $this->assertStringContainsString('$mappedplans === $totalplans', $translations);
        $this->assertStringContainsString("'accessscopeid' => \$s->id", $renderer);
    }

    public function test_n106c_strings_exist_in_all_supported_languages(): void {
        $root = dirname(__DIR__, 3);
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents($root . '/lang/' . $lang . '/local_subscriptions.php');
            foreach ([
                'commerce_plan_legacy_compatibility_title',
                'commerce_plan_legacy_mapped_readonly_desc',
                'commerce_plan_legacy_unmapped_desc',
                'commerce_plan_legacy_readonly_notice',
                'commerce_plan_open_native_prices',
                'commerce_plan_open_native_translations',
                'commerce_scope_legacy_compatibility_title',
                'commerce_scope_legacy_mapped_readonly_desc',
                'commerce_scope_legacy_unmapped_desc',
                'commerce_scope_legacy_readonly_notice',
                'commerce_scope_legacy_readonly_badge',
                'commerce_scope_open_native_products',
            ] as $key) {
                $this->assertStringContainsString("\$string['{$key}']", $source, $lang . ':' . $key);
            }
        }
    }
}
