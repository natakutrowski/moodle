<?php

namespace local_subscriptions;

final class commerce_plans_n106b_test extends \advanced_testcase {
    public function test_plan_pages_live_under_products_navigation_and_use_crm_headers(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            'admin/commerce/plans/index.php',
            'admin/commerce/plans/edit.php',
            'admin/commerce/plans/view.php',
            'admin/plans/entitlements.php',
            'admin/plans/upgrades.php',
        ] as $relative) {
            $source = file_get_contents($root . '/' . $relative);
            $this->assertStringContainsString('CommerceSectionNavigationRenderer::PRODUCTS', $source, $relative);
            $this->assertStringNotContainsString('CommerceSectionNavigationRenderer::CONFIGURATION', $source, $relative);
            $this->assertStringContainsString('CrmPageHeader::render', $source, $relative);
        }
    }

    public function test_plan_list_and_view_expose_native_product_mapping(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/plans/index.php');
        $view = file_get_contents($root . '/admin/commerce/plans/view.php');
        foreach ([$index, $view] as $source) {
            $this->assertStringContainsString('local_subs_commerce_prod_map', $source);
            $this->assertStringContainsString("subscription_plan", $source);
            $this->assertStringContainsString('commerce_plan_native_product', $source);
            $this->assertStringContainsString("'origin' => 'native'", $source);
        }
    }

    public function test_plan_rule_pages_use_polished_cards_links_and_trash_actions(): void {
        $root = dirname(__DIR__, 3);
        $entitlements = file_get_contents($root . '/admin/plans/entitlements.php');
        $upgrades = file_get_contents($root . '/admin/plans/upgrades.php');
        $this->assertStringContainsString('commerce-plan-rules-card', $entitlements);
        $this->assertStringContainsString("'/course/view.php'", $entitlements);
        $this->assertStringContainsString('fa fa-trash', $entitlements);
        $this->assertStringContainsString('commerce-plan-rules-card', $upgrades);
        $this->assertStringContainsString('commerce_plan_view_page()', $upgrades);
        $this->assertStringContainsString('fa fa-trash', $upgrades);
    }

    public function test_plan_forms_use_native_moodle_autocomplete_for_relations(): void {
        $root = dirname(__DIR__, 3);
        $planform = file_get_contents($root . '/forms/plan_form.php');
        $entitlementform = file_get_contents($root . '/forms/plan_entitlement_form.php');
        $upgradeform = file_get_contents($root . '/forms/plan_upgrade_form.php');
        $this->assertStringContainsString("addElement('autocomplete', 'accessscopeid'", $planform);
        $this->assertStringContainsString("addElement('autocomplete', 'courseid'", $entitlementform);
        $this->assertStringContainsString("addElement('autocomplete', 'roleshortname'", $entitlementform);
        $this->assertStringContainsString("'autocomplete',\n            'fromplanid'", $upgradeform);
        $this->assertStringContainsString("'autocomplete',\n            'toplanid'", $upgradeform);
        $this->assertStringNotContainsString("'class' => 'select2'", $upgradeform);
    }

    public function test_plan_status_is_embedded_in_business_information_card(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents($root . '/admin/commerce/plans/view.php');
        $this->assertStringContainsString("html_writer::div(\$statushtml, 'commerce-plan-business-status')", $view);
        $this->assertStringContainsString('commerce-plan-business-header', $view);
        $this->assertStringNotContainsString("commerce-plan-view-status mb-3", $view);
    }

    public function test_rule_pages_expose_context_strip_and_card_level_primary_action(): void {
        $root = dirname(__DIR__, 3);
        foreach (['entitlements.php', 'upgrades.php'] as $file) {
            $source = file_get_contents($root . '/admin/plans/' . $file);
            $this->assertStringContainsString('commerce-plan-rules-context', $source, $file);
            $this->assertStringContainsString('commerce-plan-rules-header-actions', $source, $file);
            $this->assertStringContainsString('commerce-plan-rules-note', $source, $file);
            $this->assertStringContainsString('commerce-plan-rule-form-card', $source, $file);
        }
    }

    public function test_plan_view_technical_id_is_prefixed_and_scope_courses_are_clickable(): void {
        $root = dirname(__DIR__, 3);
        $planview = file_get_contents($root . '/admin/commerce/plans/view.php');
        $scopeview = file_get_contents($root . '/admin/commerce/accessscopes/view.php');
        $this->assertStringContainsString("'#' . (int)\$plan->id", $planview);
        $this->assertStringContainsString("'#' . (int)\$scope->id", $scopeview);
        $this->assertStringContainsString("new moodle_url('/course/view.php', ['id' => \$course->id])", $scopeview);
    }
}
