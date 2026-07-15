<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;

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

        foreach (
            $runtime->accounts->get_enabled()
            as $initialaccount
        ) {
            $account = $initialaccount;

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
                $account->configuration[
                    'sync'
                ]['folders']
                ?? ['inbox'];

            if (!is_array($foldertypes)) {
                $foldertypes = ['inbox'];
            }

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
                        '[CRM Inbox] Folder not resolved: ' .
                        $foldertype
                    );

                    continue;
                }

                do {
                    $result =
                        $runtime->sync->sync_folder(
                            $account,
                            $folder,
                            $batchsize
                        );

                    mtrace(sprintf(
                        '[CRM Inbox] %s/%s: ' .
                        'fetched=%d ' .
                        'created=%d ' .
                        'skipped=%d ' .
                        'errors=%d',
                        $account->email,
                        $folder,
                        $result->fetched,
                        $result->created,
                        $result->skipped,
                        $result->errors
                    ));

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

                    $account = $refreshedaccount;
                } while (
                    $result->hasmore &&
                    $result->errors === 0
                );
            }
        }
    }
}