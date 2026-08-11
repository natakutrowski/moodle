<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_bulk_grant_dry_run_k14cd_test extends advanced_testcase {

    public function test_bulk_dry_run_supports_legacy_and_native_sources(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/grant/CommerceBulkGrantDryRunService.php'
        );

        $this->assertStringContainsString("SOURCE_LEGACY_PLAN = 'legacy_plan'", $source);
        $this->assertStringContainsString("SOURCE_NATIVE_PRODUCT = 'native_product'", $source);
        $this->assertStringContainsString('user_subscription', $source);
        $this->assertStringContainsString('CommercePersistenceSchema::TABLE_PURCHASE', $source);
        $this->assertStringContainsString('CommerceStorefrontOwnershipResolver', $source);
        $this->assertStringContainsString('CommerceManualProductGrantService', $source);
        $this->assertStringNotContainsString('->grant(', $source);
    }

    public function test_bulk_page_is_dry_run_only_and_links_manual_grant(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        $this->assertStringContainsString('->simulate(', $page);
        $this->assertStringContainsString('add_manual_subscription_page', $page);
        $this->assertStringContainsString('commerce_bulk_grant_no_mutation', $page);
        $this->assertStringNotContainsString('CommerceManualProductGrantService', $page);
    }

    public function test_commerce_dashboard_exposes_grants_workspace(): void {
        $root = dirname(__DIR__, 3);
        $workspace = (string)file_get_contents(
            $root . '/classes/crm/commerce/rendering/CommerceWorkspaceRenderer.php'
        );
        $registry = (string)file_get_contents(
            $root . '/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php'
        );

        $this->assertStringContainsString('/admin/commerce/grants/index.php', $workspace);
        $this->assertStringContainsString("public const GRANTS = 'grants';", $registry);
    }
}
