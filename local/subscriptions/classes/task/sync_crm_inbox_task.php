<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\services\InboxSyncFolderPolicy;
use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;
use local_subscriptions\crm\inbox\repositories\InboxSyncLogRepository;

final class sync_crm_inbox_task extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_sync_crm_inbox',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $runtime = (
            new InboxSyncRuntimeFactory()
        )->create_runtime();

        $synclogs = new InboxSyncLogRepository();

        foreach (
            $runtime->accounts->get_enabled()
            as $initialaccount
        ) {
            try {
                $recovered =
                    $synclogs->close_stale_running(
                        $initialaccount->id,
                        time() - (30 * MINSECS)
                    );

                if ($recovered > 0) {
                    mtrace(sprintf(
                        '[CRM Inbox] %s: recovered %d stale sync run(s).',
                        $initialaccount->email,
                        $recovered
                    ));
                }

                $runtime->discovery->discover(
                    $initialaccount,
                    true
                );

                $account =
                    $runtime->accounts->find(
                        $initialaccount->id
                    ) ?? $initialaccount;

                $batchsize = max(
                    1,
                    min(
                        200,
                        (int)(
                            $account->configuration[
                                'sync'
                            ]['batchsize']
                            ?? 50
                        )
                    )
                );

                $foldertypes =
                    (new InboxSyncFolderPolicy())
                        ->folder_types($account);

                $configuredfolders =
                    $account->configuration[
                        'folders'
                    ]
                    ?? [];

                if (!is_array($configuredfolders)) {
                    $configuredfolders = [];
                }

                foreach (
                    $foldertypes
                    as $foldertype
                ) {
                    $foldertype = trim(
                        (string)$foldertype
                    );

                    if ($foldertype === '') {
                        continue;
                    }

                    $folder = trim(
                        (string)(
                            $configuredfolders[
                                $foldertype
                            ]
                            ?? ''
                        )
                    );

                    if ($folder === '') {
                        mtrace(
                            '[CRM Inbox] Folder not resolved: '
                            . $foldertype
                        );
                        continue;
                    }

                    try {
                        $iterations = 0;
                        $previouscursor = null;
                        $result = null;

                        do {
                            $iterations++;

                            $result =
                                $runtime->sync->sync_folder(
                                    $account,
                                    $folder,
                                    $batchsize
                                );

                            mtrace(sprintf(
                                '[CRM Inbox] %s/%s: '
                                . 'fetched=%d created=%d '
                                . 'skipped=%d errors=%d',
                                $account->email,
                                $folder,
                                $result->fetched,
                                $result->created,
                                $result->skipped,
                                $result->errors
                            ));

                            if (
                                $result->hasmore
                                && $result->errors === 0
                                && $previouscursor !== null
                                && $result->cursor === $previouscursor
                            ) {
                                mtrace(sprintf(
                                    '[CRM Inbox] %s/%s: '
                                    . 'cursor stalled; stopping this cycle.',
                                    $account->email,
                                    $folder
                                ));
                                break;
                            }

                            $previouscursor =
                                $result->cursor;

                            $refreshedaccount =
                                $runtime->accounts->find(
                                    $account->id
                                );

                            if (!$refreshedaccount) {
                                throw new \moodle_exception(
                                    'crm_inbox_account_not_found',
                                    'local_subscriptions'
                                );
                            }

                            $account =
                                $refreshedaccount;
                        } while (
                            $result->hasmore
                            && $result->errors === 0
                            && $iterations < 100
                        );

                        if (
                            $iterations >= 100
                            && $result
                            && $result->hasmore
                        ) {
                            mtrace(sprintf(
                                '[CRM Inbox] %s/%s: '
                                . 'batch safety limit reached; '
                                . 'remaining mail will continue next run.',
                                $account->email,
                                $folder
                            ));
                        }

                        if (
                            $result
                            && $result->errors === 0
                        ) {
                            $reconciliation =
                                $runtime->sync
                                    ->reconcile_folder(
                                        $account,
                                        $folder
                                    );

                            mtrace(sprintf(
                                '[CRM Inbox] %s/%s reconciliation: '
                                . 'checked=%d updated=%d '
                                . 'moved=%d missing=%d',
                                $account->email,
                                $folder,
                                $reconciliation->checked,
                                $reconciliation->updated,
                                $reconciliation->moved,
                                $reconciliation->missing
                            ));
                        }
                    } catch (\Throwable $exception) {
                        $runtime->accounts->record_error(
                            $account->id,
                            $exception->getMessage()
                        );

                        mtrace(sprintf(
                            '[CRM Inbox] %s/%s failed: %s',
                            $account->email,
                            $folder,
                            $exception->getMessage()
                        ));

                        /*
                         * One broken folder must not stop other folders
                         * or other mailboxes from synchronising.
                         */
                        continue;
                    }
                }
            } catch (\Throwable $exception) {
                $runtime->accounts->record_error(
                    $initialaccount->id,
                    $exception->getMessage()
                );

                mtrace(sprintf(
                    '[CRM Inbox] Account %s failed: %s',
                    $initialaccount->email,
                    $exception->getMessage()
                ));

                /*
                 * Isolation is deliberate: a credential/folder problem on
                 * one mailbox must never starve every other Inbox account.
                 */
                continue;
            }
        }
    }
}