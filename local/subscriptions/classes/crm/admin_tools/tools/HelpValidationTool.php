<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\help\validation\HelpCenterValidator;

/**
 * Validates Help Center content and translations.
 */
final class HelpValidationTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::HELP_VALIDATION;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_help_validation',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_help_validation_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '?';
    }

    public function required_capability(): string {
        return Capabilities::MANAGE_CONFIGURATION;
    }

    public function risk_level(): string {
        return AdminToolRiskLevels::LOW;
    }

    public function requires_confirmation(): bool {
        return false;
    }

    public function lock_key(): string {
        return
            'local_subscriptions_' .
            AdminToolKeys::HELP_VALIDATION;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $result =
            (new HelpCenterValidator())
                ->validate();

        $details = [
            'successcount' =>
                $result->success_count(),

            'warningcount' =>
                $result->warning_count(),

            'errorcount' =>
                $result->error_count(),

            'successes' =>
                $result->successes(),

            'warnings' =>
                $result->warnings(),

            'errors' =>
                $result->errors(),
        ];

        if (!$result->is_valid()) {
            return AdminToolExecutionResult::failed(
                get_string(
                    'crm_admin_tool_help_validation_failed',
                    'local_subscriptions'
                ),
                $details
            );
        }

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_help_validation_success',
                'local_subscriptions'
            ),
            $details
        );
    }
}