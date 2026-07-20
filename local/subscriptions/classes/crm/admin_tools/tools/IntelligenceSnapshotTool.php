<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\intelligence\history\CrmScoreSnapshotRunner;

/**
 * Recomputes and stores CRM intelligence score snapshots.
 */
final class IntelligenceSnapshotTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::INTELLIGENCE_SNAPSHOT;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_intelligence',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_intelligence_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '◆';
    }

    public function required_capability(): string {
        return Capabilities::MANAGE_CONFIGURATION;
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
            AdminToolKeys::INTELLIGENCE_SNAPSHOT;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $limit = max(
            1,
            min(
                5000,
                (int)(
                    $context->parameters['limit']
                    ?? 500
                )
            )
        );

        $processed =
            (new CrmScoreSnapshotRunner())
                ->run($limit);

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_intelligence_success',
                'local_subscriptions'
            ),
            [
                'limit' => $limit,
                'processed' => $processed,
            ]
        );
    }
}