<?php

namespace local_subscriptions\crm\admin_tools\tools;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\AdminToolKeys;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
use local_subscriptions\crm\inbox\services\InboxDiagnosticsService;

/**
 * Runs CRM Inbox connectivity and integrity diagnostics.
 */
final class InboxDiagnosticsTool implements AdminTool {

    public function key(): string {
        return AdminToolKeys::INBOX_DIAGNOSTICS;
    }

    public function title(): string {
        return get_string(
            'crm_admin_tool_inbox_diagnostics',
            'local_subscriptions'
        );
    }

    public function description(): string {
        return get_string(
            'crm_admin_tool_inbox_diagnostics_desc',
            'local_subscriptions'
        );
    }

    public function icon(): string {
        return '◉';
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
            AdminToolKeys::INBOX_DIAGNOSTICS;
    }

    public function execute(
        AdminToolExecutionContext $context
    ): AdminToolExecutionResult {
        $credentials =
            new MoodleConfigInboxCredentialStore();

        $service =
            new InboxDiagnosticsService(
                new InboxAccountRepository(),
                new InboxDiagnosticsRepository(),
                $credentials,
                new OvhImapConnector(
                    $credentials,
                    new ImapMimeParser()
                ),
                new OvhSmtpConnector(
                    $credentials
                )
            );

        $report = $service->diagnose();

        $failedchecks = array_values(
            array_filter(
                $report['checks'] ?? [],
                static fn(array $check): bool =>
                    empty($check['success'])
            )
        );

        $details = [
            'checkcount' =>
                count($report['checks'] ?? []),

            'failedcheckcount' =>
                count($failedchecks),

            'metrics' =>
                $report['metrics'] ?? [],

            'checks' =>
                $report['checks'] ?? [],
        ];

        if ($failedchecks !== []) {
            return AdminToolExecutionResult::failed(
                get_string(
                    'crm_admin_tool_inbox_diagnostics_failed',
                    'local_subscriptions'
                ),
                $details
            );
        }

        return AdminToolExecutionResult::success(
            get_string(
                'crm_admin_tool_inbox_diagnostics_success',
                'local_subscriptions'
            ),
            $details
        );
    }
}