<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_bulk_grant_campaign_k14ef_test extends advanced_testcase {

    public function test_snapshot_and_execution_are_separated(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/grant/CommerceBulkGrantCampaignService.php'
        );

        $this->assertStringContainsString('create_snapshot', $service);
        $this->assertStringContainsString('CommerceBulkGrantDryRunService', $service);
        $this->assertStringContainsString('process_member', $service);

        $processpos = strpos($service, 'public function process(');
        $processtail = substr($service, $processpos);
        $this->assertStringNotContainsString('->simulate(', $processtail);
    }

    public function test_executor_uses_idempotent_manual_grant_service_and_runtime_ownership_check(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/grant/CommerceBulkGrantCampaignService.php'
        );

        $this->assertStringContainsString('CommerceManualProductGrantService', $service);
        $this->assertStringContainsString('CommerceStorefrontOwnershipResolver', $service);
        $this->assertStringContainsString('retry_failures', $service);
        $this->assertStringContainsString('MEMBER_FAILED', $service);
    }

    public function test_bulk_ui_has_selection_snapshot_and_campaign_view(): void {
        $root = dirname(__DIR__, 3);
        $bulk = (string)file_get_contents($root . '/admin/commerce/grants/bulk.php');
        $view = (string)file_get_contents($root . '/admin/commerce/grants/campaign_view.php');

        $this->assertStringContainsString("'selected_userids[]'", $bulk);
        $this->assertStringContainsString("'campaign_name'", $bulk);
        $this->assertStringContainsString("'action',", $bulk);
        $this->assertStringContainsString("'snapshot'", $bulk);
        $this->assertStringContainsString("'launch'", $view);
        $this->assertStringContainsString("'retry'", $view);
    }

    public function test_scheduled_task_is_registered(): void {
        $root = dirname(__DIR__, 3);
        $tasks = (string)file_get_contents($root . '/db/tasks.php');

        $this->assertStringContainsString(
            'process_commerce_grant_campaigns_task',
            $tasks
        );
    }
}
