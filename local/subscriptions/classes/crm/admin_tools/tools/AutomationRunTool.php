<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\automation\AutomationCronRunner;

/**
 * Runs CRM automation scanners.
 */
final class AutomationRunTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::AUTOMATIONS;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_automations',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_automations_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '⚙';
    }

    public function required_capability(): string {
        return Capabilities::MANAGE_CONFIGURATION;
    }

    public function risk_level(): string {
        return AdminToolRiskLevels::HIGH;
    }

    public function requires_confirmation(): bool {
        return true;
    }

    public function lock_key(): string {
        return
            'local_subscriptions_' .
            AdminToolKeys::AUTOMATIONS;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        (new AutomationCronRunner())->run();

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_automations_success',
                'local_subscriptions'
            )
        );
    }
}