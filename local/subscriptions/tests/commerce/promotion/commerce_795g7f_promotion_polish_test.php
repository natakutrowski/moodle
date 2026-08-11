<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

final class commerce_795g7f_promotion_polish_test extends \advanced_testcase {
    public function test_configuration_navigation_opens_the_new_hub(): void {
        $registry = file_get_contents(__DIR__ . '/../../../classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php');
        $hub = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/index.php');
        $this->assertStringContainsString('/admin/commerce/configuration/index.php', $registry);
        $this->assertStringContainsString('commerce_configuration_scopes_title', $hub);
        $this->assertStringContainsString('commerce_configuration_plans_title', $hub);
        $this->assertStringContainsString('commerce_configuration_promotions_title', $hub);
    }

    public function test_promotion_editor_uses_business_friendly_controls(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/promotions/edit.php');
        $this->assertStringContainsString('CommerceCurrencyRegistry', $source);
        $this->assertStringContainsString('multiple', $source);
        $this->assertStringContainsString("'__all__'", $source);
        $this->assertStringContainsString('$discountvalue * 100', $source);
        $this->assertStringContainsString('commerce_promotion_back_to_list', $source);
    }

    public function test_cart_lines_have_compact_spacing_contract(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');
        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        $this->assertStringContainsString('commerce-cart-line__body', $template);
        $this->assertStringContainsString('commerce-cart-line__actions', $template);
        $this->assertStringContainsString('.commerce-cart-line__body', $styles);
        $this->assertStringContainsString('.commerce-cart-line__action', $styles);
    }
}
