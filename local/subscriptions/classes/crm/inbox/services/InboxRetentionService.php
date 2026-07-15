<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\crm\inbox\repositories\InboxRetentionRepository;

final class InboxRetentionService {

    public function __construct(
        private readonly InboxRetentionRepository $repository
    ) {
    }

    public function cleanup(
        int $closeddays,
        int $deletedays,
        int $logdays,
        int $limit = 100,
        bool $dryrun = true
    ): array {
        $threadids =
            $this->repository
                ->get_expired_thread_ids(
                    $closeddays,
                    $deletedays,
                    $limit
                );

        if ($dryrun) {
            return [
                'dryrun' => true,
                'threadids' => $threadids,
                'threadcount' =>
                    count($threadids),
                'deletedlogs' => 0,
            ];
        }

        $context =
            context_system::instance();

        $filestorage =
            get_file_storage();

        foreach ($threadids as $threadid) {
            $fileitemids =
                $this->repository
                    ->get_file_item_ids_for_thread(
                        $threadid
                    );

            foreach (
                $fileitemids
                as $fileitemid
            ) {
                $filestorage->delete_area_files(
                    $context->id,
                    'local_subscriptions',
                    'inbox_attachment',
                    $fileitemid
                );
            }

            $this->repository->delete_thread(
                $threadid
            );
        }

        $deletedlogs =
            $this->repository
                ->delete_old_sync_logs(
                    $logdays
                );

        $this->repository
            ->delete_orphan_contacts();

        return [
            'dryrun' => false,
            'threadids' => $threadids,
            'threadcount' =>
                count($threadids),
            'deletedlogs' =>
                $deletedlogs,
        ];
    }
}