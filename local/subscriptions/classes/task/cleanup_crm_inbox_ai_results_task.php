<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiResultRepository;

final class cleanup_crm_inbox_ai_results_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_cleanup_crm_inbox_ai_results',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $repository =
            new InboxAiResultRepository();

        $total = 0;

        do {
            $deleted =
                $repository->delete_expired(
                    time(),
                    1000
                );

            $total += $deleted;
        } while ($deleted === 1000);

        mtrace(
            '[CRM Inbox AI] Expired results deleted: ' .
            $total
        );
    }
}