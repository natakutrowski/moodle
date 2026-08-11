<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_catalog_e19c_plan_rules_test extends \advanced_testcase {
    public function test_plan_view_restores_entitlements_and_upgrade_sections(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/plans/view.php');

        $this->assertStringContainsString('subscription_plan_entitlement', $source);
        $this->assertStringContainsString('subscription_plan_upgrade', $source);
        $this->assertStringContainsString('plan_entitlements_page()', $source);
        $this->assertStringContainsString('plan_upgrades_page()', $source);
    }

    public function test_entitlements_page_returns_to_commerce_plan(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/plans/entitlements.php');

        $this->assertStringContainsString('commerce_plan_view_page()', $source);
        $this->assertStringContainsString('CommerceSectionNavigationRenderer::render', $source);
        $this->assertStringNotContainsString("manage_page(), ['tab' => 'plans']", $source);
    }

    public function test_upgrade_page_can_be_filtered_by_plan(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/plans/upgrades.php');

        $this->assertStringContainsString("optional_param('planid'", $source);
        $this->assertStringContainsString('u.fromplanid = :fromplanid OR u.toplanid = :toplanid', $source);
        $this->assertStringContainsString('$defaults->fromplanid = $planid', $source);
        $this->assertStringContainsString('commerce_plan_view_page()', $source);
    }
}
