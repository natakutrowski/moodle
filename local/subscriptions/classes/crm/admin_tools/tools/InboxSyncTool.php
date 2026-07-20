<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\inbox\services\InboxManualSyncService;
use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;

/**
 * Manually synchronises enabled CRM Inbox accounts.
 */
final class InboxSyncTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::INBOX_SYNC;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_inbox_sync',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_inbox_sync_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '✉';
    }

    public function required_capability(): string {
        return Capabilities::MANAGE_INBOX;
    }

    public function risk_level(): string {
        return AdminToolRiskLevels::NORMAL;
    }

    public function requires_confirmation(): bool {
        return true;
    }

    public function lock_key(): string {
        return
            'local_subscriptions_' .
            AdminToolKeys::INBOX_SYNC;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $runtime =
            (new InboxSyncRuntimeFactory())
                ->create_runtime();

        $summary =
            (new InboxManualSyncService(
                $runtime->accounts,
                $runtime->sync
            ))->sync_enabled_accounts();

        if ((int)$summary['errors'] > 0) {
            return AdminToolExecutionResult::failed(
                get_string(
                    'crm_admin_tool_inbox_sync_partial',
                    'local_subscriptions'
                ),
                $summary
            );
        }

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_inbox_sync_success',
                'local_subscriptions'
            ),
            $summary
        );
    }
}