<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;

final class InboxManualSyncService {

    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxSyncService $sync
    ) {
    }

    public function sync_enabled_accounts(): array {
        $summary = [
            'accountcount' => 0,
            'foldercount' => 0,
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'hasmore' => false,
        ];

        foreach (
            $this->accounts->get_enabled()
            as $account
        ) {
            $summary['accountcount']++;

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

            if (
                !is_array(
                    $configuredfolders
                )
            ) {
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
                    $summary['errors']++;
                    continue;
                }

                $summary['foldercount']++;

                $result =
                    $this->sync->sync_folder(
                        $account,
                        $folder,
                        $batchsize
                    );

                $summary['fetched'] +=
                    $result->fetched;

                $summary['created'] +=
                    $result->created;

                $summary['updated'] +=
                    $result->updated;

                $summary['skipped'] +=
                    $result->skipped;

                $summary['errors'] +=
                    $result->errors;

                if ($result->hasmore) {
                    $summary['hasmore'] = true;
                }
            }
        }

        return $summary;
    }
}