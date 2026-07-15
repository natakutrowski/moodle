<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxRetentionRepository;
use local_subscriptions\crm\inbox\services\InboxRetentionService;

final class cleanup_crm_inbox_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_cleanup_crm_inbox',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $accounts = new InboxAccountRepository();

        $account = $accounts->find_by_email(
            'support@campusfr.fr'
        );

        if (!$account || !$account->enabled) {
            mtrace(
                '[CRM Inbox] No enabled Inbox account available for cleanup.'
            );

            return;
        }

        $retention = $account->configuration[
            'retention'
        ] ?? [];

        if (!is_array($retention)) {
            $retention = [];
        }

        $closeddays = max(
            1,
            (int)(
                $retention['closed_days']
                ?? 730
            )
        );

        $deletedays = max(
            1,
            (int)(
                $retention[
                    'locally_deleted_days'
                ] ?? 30
            )
        );

        $logdays = max(
            1,
            (int)(
                $retention['sync_logs_days']
                ?? 90
            )
        );

        $service = new InboxRetentionService(
            new InboxRetentionRepository()
        );

        $result = $service->cleanup(
            $closeddays,
            $deletedays,
            $logdays,
            100,
            false
        );

        mtrace(sprintf(
            '[CRM Inbox] Cleanup completed: threads=%d, sync logs=%d',
            $result['threadcount'],
            $result['deletedlogs']
        ));
    }
}