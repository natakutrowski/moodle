<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
use local_subscriptions\crm\inbox\repositories\InboxSyncLogRepository;
use local_subscriptions\crm\inbox\validation\InboxAccountValidator;
use local_subscriptions\crm\inbox\services\InboxFolderResolver;

final class InboxDiagnosticsService {

    private const TABLES = [
        'local_subscriptions_inbox_account',
        'local_subscriptions_inbox_contact',
        'local_subscriptions_inbox_team',
        'local_subscriptions_inbox_team_member',
        'local_subscriptions_inbox_thread',
        'local_subscriptions_inbox_message',
        'local_subscriptions_inbox_participant',
        'local_subscriptions_inbox_attachment',
        'local_subscriptions_inbox_tag',
        'local_subscriptions_inbox_thread_tag',
        'local_subscriptions_inbox_template',
        'local_subscriptions_inbox_sync_log',
        'local_subscriptions_inbox_remote',
    ];

    private readonly InboxAccountValidator $validator;

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxDiagnosticsRepository $repository,
        private readonly InboxCredentialStoreInterface $credentials,
        private readonly InboxConnectorInterface $inbound,
        private readonly InboxOutboundConnectorInterface $outbound,
        private readonly InboxSyncLogRepository $synclogs =
            new InboxSyncLogRepository(),
        ?InboxAccountValidator $validator = null
    ) {
        $this->validator =
            $validator ??
            new InboxAccountValidator($credentials);
    }

    public function diagnose(
        ?int $accountid = null
    ): array {
        $checks = [];
        $folderstatus = [];

        $checks[] = $this->check(
            'imap_extension',
            extension_loaded('imap'),
            extension_loaded('imap')
                ? get_string('crm_inbox_diagnostics_check_imap_extension_ok', 'local_subscriptions')
                : get_string('crm_inbox_diagnostics_check_imap_extension_missing', 'local_subscriptions')
        );

        foreach (self::TABLES as $table) {
            $exists = $this->repository
                ->table_exists($table);

            $checks[] = $this->check(
                'table_' . $table,
                $exists,
                $exists
                    ? get_string('crm_inbox_diagnostics_check_table_available', 'local_subscriptions', $table)
                    : get_string('crm_inbox_diagnostics_check_table_missing', 'local_subscriptions', $table)
            );
        }

        if ($accountid !== null) {
            $account = $this->accounts->find(
                $accountid
            );
        } else {
            $enabledaccounts =
                $this->accounts->get_enabled();

            $account =
                $enabledaccounts[0] ?? null;
        }

        if (!$account) {
            $checks[] = $this->check(
                'account',
                false,
                get_string('crm_inbox_diagnostics_check_account_missing', 'local_subscriptions')
            );

            return $this->result($checks, null);
        }

        $checks[] = $this->check(
            'account',
            true,
            $account->email
        );

        $checks[] = $this->check(
            'account_enabled',
            $account->enabled,
            $account->enabled
                ? get_string('crm_inbox_diagnostics_check_account_enabled', 'local_subscriptions')
                : get_string('crm_inbox_diagnostics_check_account_disabled', 'local_subscriptions')
        );

        $validation = $this->validator->validate(
            $account
        );

        foreach ($validation->errors as $index => $error) {
            $checks[] = $this->check(
                'account_validation_error_' . $index,
                false,
                $error
            );
        }

        foreach ($validation->warnings as $index => $warning) {
            $checks[] = $this->check(
                'account_validation_warning_' . $index,
                true,
                $warning
            );
        }

        $credentialkey =
            $account->credentialkey ?? '';

        $credentialsavailable =
            $credentialkey !== '' &&
            $this->credentials->has(
                $credentialkey
            );

        $checks[] = $this->check(
            'credentials',
            $credentialsavailable,
            $credentialsavailable
                ? get_string('crm_inbox_diagnostics_check_credentials_available', 'local_subscriptions')
                : get_string('crm_inbox_diagnostics_check_credentials_missing', 'local_subscriptions')
        );

        if (
            $account->enabled &&
            $credentialsavailable &&
            extension_loaded('imap') &&
            $validation->is_valid()
        ) {
            try {
                $this->inbound->test_connection(
                    $account
                );

                $checks[] = $this->check(
                    'imap_connection',
                    true,
                    get_string('crm_inbox_diagnostics_check_imap_connection_ok', 'local_subscriptions')
                );
            } catch (\Throwable $exception) {
                $checks[] = $this->check(
                    'imap_connection',
                    false,
                    $exception->getMessage()
                );
            }

            try {
                $discovery =
                    (new InboxFolderDiscoveryService(
                        $this->inbound,
                        new InboxFolderResolver(),
                        $this->accounts
                    ))->discover(
                        $account,
                        true
                    );

                $folders = $discovery['folders'];
                $resolved = $discovery['resolved'];
                $missing = $discovery['missing'];

                $account = $this->accounts->find(
                    $account->id
                ) ?? $account;

                $checks[] = $this->check(
                    'imap_folders',
                    empty($missing),
                    empty($missing)
                        ? get_string(
                            'crm_inbox_folder_discovery_success',
                            'local_subscriptions',
                            (object)[
                                'count' => count($folders),
                                'inbox' =>
                                    $resolved['inbox'] ?? '-',
                                'sent' =>
                                    $resolved['sent'] ?? '-',
                                'trash' =>
                                    $resolved['trash'] ?? '-',
                                'archive' =>
                                    $resolved['archive'] ?? '-',
                                'drafts' =>
                                    $resolved['drafts'] ?? '-',
                            ]
                        )
                        : get_string(
                            'crm_inbox_folder_discovery_missing',
                            'local_subscriptions',
                            implode(', ', $missing)
                        )
                );

                $syncfolders =
                    (new InboxSyncFolderPolicy())
                        ->folder_types($account);

                $baselineok =
                    !empty($resolved['inbox'])
                    && !empty($resolved['sent'])
                    && in_array('inbox', $syncfolders, true)
                    && in_array('sent', $syncfolders, true);

                $checks[] = $this->check(
                    'sync_folder_baseline',
                    $baselineok,
                    get_string(
                        $baselineok
                            ? 'crm_inbox_o1_sync_baseline_ok'
                            : 'crm_inbox_o1_sync_baseline_problem',
                        'local_subscriptions'
                    )
                );

                foreach (
                    ['inbox', 'sent', 'drafts', 'archive', 'trash']
                    as $type
                ) {
                    $folder = trim(
                        (string)($resolved[$type] ?? '')
                    );

                    if ($folder === '') {
                        continue;
                    }

                    $incremental =
                        $account->syncstate['folders'][$folder]
                        ?? [];

                    $reconciliation =
                        $account->syncstate['reconciliation'][$folder]
                        ?? [];

                    $folderstatus[] = [
                        'type' => $type,
                        'folder' => $folder,
                        'enabled' => in_array(
                            $type,
                            $syncfolders,
                            true
                        ),
                        'incrementalat' =>
                            (int)($incremental['updatedat'] ?? 0),
                        'reconciledat' =>
                            (int)($reconciliation['updatedat'] ?? 0),
                        'checked' =>
                            (int)($reconciliation['checked'] ?? 0),
                        'updated' =>
                            (int)($reconciliation['updated'] ?? 0),
                        'moved' =>
                            (int)($reconciliation['moved'] ?? 0),
                        'missing' =>
                            (int)($reconciliation['missing'] ?? 0),
                    ];
                }
            } catch (\Throwable $exception) {
                $checks[] = $this->check(
                    'imap_folders',
                    false,
                    $exception->getMessage()
                );
            }

            try {
                $this->outbound->test_connection(
                    $account
                );

                $checks[] = $this->check(
                    'smtp_connection',
                    true,
                    get_string('crm_inbox_diagnostics_check_smtp_connection_ok', 'local_subscriptions')
                );
            } catch (\Throwable $exception) {
                $checks[] = $this->check(
                    'smtp_connection',
                    false,
                    $exception->getMessage()
                );
            }
        }

        $latestlog = $this->repository
            ->latest_sync_log($account->id);

        $metrics = [
            'threads' => $this->repository->count(
                'local_subscriptions_inbox_thread',
                ['locallydeleted' => 0]
            ),
            'messages' => $this->repository->count(
                'local_subscriptions_inbox_message'
            ),
            'contacts' => $this->repository->count(
                'local_subscriptions_inbox_contact'
            ),
            'unmatchedcontacts' =>
                $this->repository
                    ->unmatched_contact_count(),
            'ambiguouscontacts' =>
                $this->repository
                    ->ambiguous_contact_count(),
            'pendingattachments' =>
                $this->repository
                    ->pending_attachment_count(),
            'failedattachments' =>
                $this->repository
                    ->failed_attachment_count(),
        ];

        $now = time();
        $last24hours = $now - DAYSECS;
        $staleafter = $now - (30 * MINSECS);

        $operational = [
            'lastsuccessat' =>
                $this->repository
                    ->last_successful_sync_at(
                        $account->id
                    ),

            'failed24h' =>
                $this->synclogs
                    ->count_status_since(
                        $account->id,
                        ['failed'],
                        $last24hours
                    ),

            'partial24h' =>
                $this->synclogs
                    ->count_status_since(
                        $account->id,
                        ['partial'],
                        $last24hours
                    ),

            'stalerunning' =>
                $this->synclogs
                    ->stale_running_count(
                        $account->id,
                        $staleafter
                    ),

            'duplicateidentities' =>
                $this->repository
                    ->duplicate_identity_count(
                        $account->id
                    ),

            'orphanremote' =>
                $this->repository
                    ->orphan_remote_count(
                        $account->id
                    ),

            'orphanattachments' =>
                $this->repository
                    ->orphan_attachment_count(
                        $account->id
                    ),

            'sentcopyfailures24h' =>
                $this->repository
                    ->sent_copy_failure_count(
                        $account->id,
                        $last24hours
                    ),

            'recentlogs' =>
                $this->synclogs->recent(
                    $account->id,
                    12
                ),
        ];

        $freshnessok =
            $operational['lastsuccessat'] > 0
            && (
                $now - $operational['lastsuccessat']
            ) <= (2 * HOURSECS);

        $checks[] = $this->check(
            'sync_freshness',
            $freshnessok,
            get_string(
                $freshnessok
                    ? 'crm_inbox_o14_sync_fresh_ok'
                    : 'crm_inbox_o14_sync_fresh_problem',
                'local_subscriptions'
            )
        );

        $integrityok =
            $operational['duplicateidentities'] === 0
            && $operational['orphanremote'] === 0
            && $operational['orphanattachments'] === 0;

        $checks[] = $this->check(
            'data_integrity',
            $integrityok,
            get_string(
                $integrityok
                    ? 'crm_inbox_o14_integrity_ok'
                    : 'crm_inbox_o14_integrity_problem',
                'local_subscriptions'
            )
        );

        $deliveryok =
            $operational['sentcopyfailures24h'] === 0;

        $checks[] = $this->check(
            'sent_copy_health',
            $deliveryok,
            get_string(
                $deliveryok
                    ? 'crm_inbox_o14_sentcopy_ok'
                    : 'crm_inbox_o14_sentcopy_problem',
                'local_subscriptions',
                $operational['sentcopyfailures24h']
            )
        );

        return $this->result(
            $checks,
            $account,
            $latestlog,
            $metrics,
            $folderstatus,
            $operational
        );
    }

    private function check(
        string $key,
        bool $success,
        string $message
    ): array {
        return [
            'key' => $key,
            'success' => $success,
            'message' => $message,
        ];
    }

    private function result(
        array $checks,
        ?object $account,
        ?object $latestlog = null,
        array $metrics = [],
        array $folderstatus = [],
        array $operational = []
    ): array {
        $errors = count(array_filter(
            $checks,
            static fn(array $check): bool =>
                !$check['success']
        ));

        return [
            'success' => $errors === 0,
            'errors' => $errors,
            'checks' => $checks,
            'account' => $account,
            'latestlog' => $latestlog,
            'metrics' => $metrics,
            'folderstatus' => $folderstatus,
            'operational' => $operational,
        ];
    }
}