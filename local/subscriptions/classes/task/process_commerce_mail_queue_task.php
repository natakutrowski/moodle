<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * Sends due transactional messages from the persistent outbox.
 */
final class process_commerce_mail_queue_task extends scheduled_task {

    public function get_name(): string {
        return get_string('task_process_commerce_mail_queue', 'local_subscriptions');
    }

    public function execute(): void {
        $now = time();
        $repository = new CommerceMailQueueRepository();
        $repository->recover_stale_processing($now - 1800, $now);

        $result = CommerceMailRuntime::processor()->process_due(50, $now, null, [CommerceMailType::PERSONAL_OFFER], false);
        mtrace(sprintf(
            '[Commerce Mail] processed=%d sent=%d retried=%d failed=%d skipped=%d',
            $result['processed'],
            $result['sent'],
            $result['retried'],
            $result['failed'],
            $result['skipped']
        ));
    }
}
