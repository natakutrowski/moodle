<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationBatchRunner;

/**
 * Executes a CRM Recommendation Engine batch.
 */
final class RecommendationRunTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::RECOMMENDATIONS;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_recommendations',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_recommendations_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '✦';
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
            AdminToolKeys::RECOMMENDATIONS;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $limit = max(
            1,
            min(
                1000,
                (int)(
                    $context->parameters['limit']
                    ?? 100
                )
            )
        );

        $resetcursor = !empty(
            $context->parameters['resetcursor']
        );

        $report =
            (new RecommendationBatchRunner())
                ->run(
                    limit: $limit,
                    source: 'admin_tool',
                    resetcursor: $resetcursor
                );

        $details =
            (array)$report->to_object();

        if (!$report->is_success()) {
            return AdminToolExecutionResult::failed(
                get_string(
                    'crm_admin_tool_recommendations_partial',
                    'local_subscriptions'
                ),
                $details
            );
        }

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_recommendations_success',
                'local_subscriptions'
            ),
            $details
        );
    }
}