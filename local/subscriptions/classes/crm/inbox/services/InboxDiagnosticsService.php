<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\contracts\InboxOutboundConnectorInterface;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
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
        'local_subscriptions_inbox_sync_log',
    ];

    private readonly InboxAccountValidator $validator;

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxDiagnosticsRepository $repository,
        private readonly InboxCredentialStoreInterface $credentials,
        private readonly InboxConnectorInterface $inbound,
        private readonly InboxOutboundConnectorInterface $outbound,
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

        $checks[] = $this->check(
            'imap_extension',
            extension_loaded('imap'),
            extension_loaded('imap')
                ? 'PHP IMAP enabled'
                : 'PHP IMAP extension missing'
        );

        foreach (self::TABLES as $table) {
            $exists = $this->repository
                ->table_exists($table);

            $checks[] = $this->check(
                'table_' . $table,
                $exists,
                $exists
                    ? $table . ' available'
                    : $table . ' missing'
            );
        }

        $account = $accountid !== null
            ? $this->accounts->find($accountid)
            : $this->accounts->find_by_email(
                'support@campusfr.fr'
            );

        if (!$account) {
            $checks[] = $this->check(
                'account',
                false,
                'CRM Inbox account not configured'
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
                ? 'Account enabled'
                : 'Account disabled'
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
                ? 'Credentials available'
                : 'Credentials missing'
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
                    'IMAP connection successful'
                );
            } catch (\Throwable $exception) {
                $checks[] = $this->check(
                    'imap_connection',
                    false,
                    $exception->getMessage()
                );
            }

            try {
                $folders = $this->inbound
                    ->list_folders($account);

                $resolver = new InboxFolderResolver();

                $resolved = $resolver->resolve(
                    $folders,
                    is_array(
                        $account->configuration['folders']
                        ?? null
                    )
                        ? $account->configuration['folders']
                        : []
                );

                $missing = $resolver->missing_required(
                    $folders,
                    $resolved
                );

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
                    'SMTP connection successful'
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

        return $this->result(
            $checks,
            $account,
            $latestlog,
            $metrics
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
        array $metrics = []
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
        ];
    }
}