<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_promotions_n107_test extends \advanced_testcase {
    public function test_promotions_live_under_offers_access_navigation(): void {
        $root = dirname(__DIR__, 3);
        foreach (['admin/commerce/promotions/index.php', 'admin/commerce/promotions/edit.php'] as $relative) {
            $source = file_get_contents($root . '/' . $relative);
            $this->assertIsString($source);
            $this->assertStringContainsString('CommerceSectionNavigationRenderer::OFFERS_ACCESS', $source);
            $this->assertStringContainsString('CommerceOffersAccessNavigationRenderer::PROMOTIONS', $source);
            $this->assertStringNotContainsString('CommerceSectionNavigationRenderer::CONFIGURATION', $source);
            $this->assertStringContainsString("commerce_offers_access_title", $source);
        }
    }

    public function test_offers_access_navigation_exposes_promotions_tab(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/classes/crm/commerce/rendering/CommerceOffersAccessNavigationRenderer.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("public const PROMOTIONS = 'promotions'", $source);
        $this->assertStringContainsString('/admin/commerce/promotions/index.php', $source);
        $this->assertStringContainsString('commerce_offers_access_tab_promotions', $source);
    }

    public function test_offers_access_overview_can_create_promotion(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/offers-access/index.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("['promotion', 'fa-percent'", $source);
        $this->assertStringContainsString('/admin/commerce/promotions/edit.php', $source);
    }

    public function test_promotions_ui_distinguishes_coupon_and_automatic_discount(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/promotions/index.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('commerce_promotions_coupon_hint', $source);
        $this->assertStringContainsString('commerce_promotion_coupon_badge', $source);
        $this->assertStringContainsString('commerce_promotion_automatic_badge', $source);
        $this->assertStringContainsString('fa fa-trash', $source);
    }
    public function test_promotions_n1071_uses_dashboard_and_grouped_editor_cards(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/promotions/index.php');
        $edit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        $styles = file_get_contents($root . '/styles.css');
        $this->assertIsString($index);
        $this->assertIsString($edit);
        $this->assertIsString($styles);
        $this->assertStringContainsString('commerce-promotions-metric', $index);
        $this->assertStringContainsString('commerce-promotions-list-card', $index);
        $this->assertStringContainsString('commerce_promotion_section_identity', $edit);
        $this->assertStringContainsString('commerce_promotion_section_discount', $edit);
        $this->assertStringContainsString('commerce_promotion_section_products', $edit);
        $this->assertStringContainsString('commerce-promotion-editor-actions', $edit);
        $this->assertStringContainsString('.commerce-promotion-editor-card', $styles);
    }

    public function test_promotions_n1072_adds_detail_page_and_validity_window(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/promotions/index.php');
        $edit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        $view = file_get_contents($root . '/admin/commerce/promotions/view.php');
        $this->assertIsString($index);
        $this->assertIsString($edit);
        $this->assertIsString($view);
        $this->assertStringContainsString('/admin/commerce/promotions/view.php', $index);
        $this->assertStringContainsString('commerce_promotion_validity', $index);
        $this->assertStringContainsString("'startsat' => \$formatdatetime", $edit);
        $this->assertStringContainsString("'endsat' => \$formatdatetime", $edit);
        $this->assertStringContainsString("'datetime-local'", $edit);
        $this->assertStringContainsString("\$validationdata['startsat']", $edit);
        $this->assertStringContainsString("\$validationdata['endsat']", $edit);
        $this->assertStringContainsString('commerce-promotion-view-card', $view);
        $this->assertStringContainsString('CommercePromotionEligibilityRuleSet::from_metadata', $view);
    }

    public function test_promotions_n1072_hides_skus_and_translates_product_types(): void {
        $root = dirname(__DIR__, 3);
        $edit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        $view = file_get_contents($root . '/admin/commerce/promotions/view.php');
        $this->assertIsString($edit);
        $this->assertIsString($view);
        $this->assertStringContainsString("\$productoptions[\$product->get_sku()] = \$product->get_name();", $edit);
        $this->assertStringNotContainsString("\$product->get_name() . ' — ' . \$product->get_sku()", $edit);
        foreach (['course_access', 'digital_download', 'bundle', 'service'] as $type) {
            $key = 'commerce_product_type_' . $type;
            $this->assertStringContainsString($key, $edit);
            $this->assertStringContainsString($key, $view);
        }
    }

    public function test_promotions_n1072_stacks_customer_product_conditions(): void {
        $root = dirname(__DIR__, 3);
        $edit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        $styles = file_get_contents($root . '/styles.css');
        $this->assertIsString($edit);
        $this->assertIsString($styles);
        $this->assertStringContainsString('commerce-promotion-eligibility-products', $edit);
        $this->assertGreaterThanOrEqual(2, substr_count($edit, "'col-12'"));
        $this->assertStringContainsString('.commerce-promotion-eligibility-products', $styles);
    }


    public function test_promotions_n1073_uses_compact_actions_filters_sorting_and_editorial_labels(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/promotions/index.php');
        $edit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        $view = file_get_contents($root . '/admin/commerce/promotions/view.php');
        $fr = file_get_contents($root . '/lang/fr/local_subscriptions.php');
        $styles = file_get_contents($root . '/styles.css');
        $this->assertIsString($index);
        $this->assertIsString($edit);
        $this->assertIsString($view);
        $this->assertIsString($fr);
        $this->assertIsString($styles);

        $this->assertStringContainsString("optional_param('q'", $index);
        $this->assertStringContainsString("optional_param('status'", $index);
        $this->assertStringContainsString("optional_param('mode'", $index);
        $this->assertStringContainsString("optional_param('validity'", $index);
        $this->assertStringContainsString("optional_param('sort'", $index);
        $this->assertStringContainsString("optional_param('dir'", $index);
        $this->assertStringContainsString("optional_param('page'", $index);
        $this->assertStringContainsString("optional_param('perpage'", $index);
        $this->assertStringContainsString('paging_bar', $index);
        $this->assertStringContainsString('commerce-promotions-sort-link', $index);
        $this->assertStringContainsString('crm-sales-row-actions-menu', $index);
        $this->assertStringContainsString('fa fa-ellipsis-h', $index);
        $this->assertStringContainsString("'class' => 'btn btn-sm btn-primary'", $index);

        $this->assertStringContainsString("'EUR' => '🇪🇺'", $edit);
        $this->assertStringContainsString("'EUR' => '🇪🇺'", $view);
        $this->assertStringContainsString('commerce-promotion-infinity-icon', $view);
        $this->assertStringContainsString("\$string['commerce_promotion_productskus'] = 'Produits éligibles';", $fr);
        $this->assertStringNotContainsString('SKU éligibles (un par ligne)', $fr);
        $this->assertStringContainsString('.commerce-promotions-filter-grid', $styles);
    }

}
