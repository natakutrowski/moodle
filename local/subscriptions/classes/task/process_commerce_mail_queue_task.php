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

        if ((string)get_config('local_subscriptions', 'commerce_mail_transactional_enabled') === '0') {
            mtrace('[Commerce Mail] disabled by Commerce CRM configuration.');
            return;
        }

        $batch = max(
            1,
            min(
                500,
                (int)(get_config(
                    'local_subscriptions',
                    'commerce_mail_transactional_batch_size'
                ) ?: 50)
            )
        );
        $hourly = max(
            0,
            min(
                5000,
                (int)(get_config(
                    'local_subscriptions',
                    'commerce_mail_transactional_hourly_limit'
                ) ?: 0)
            )
        );
        $globalhourly = max(
            0,
            min(
                10000,
                (int)(get_config(
                    'local_subscriptions',
                    'commerce_mail_global_hourly_limit'
                ) ?: 0)
            )
        );

        $repository = new CommerceMailQueueRepository();
        $repository->recover_stale_processing($now - 1800, $now);

        $limit = $batch;
        if ($hourly > 0) {
            $sentlasthour = $repository->count_transactional_sent_since($now - HOURSECS);
            $limit = min($limit, max(0, $hourly - $sentlasthour));
        }
        if ($globalhourly > 0) {
            $sentglobally = $repository->count_all_sent_since($now - HOURSECS);
            $limit = min($limit, max(0, $globalhourly - $sentglobally));
        }

        if ($limit <= 0) {
            mtrace('[Commerce Mail] throttled by hourly SMTP limits.');
            return;
        }

        $result = CommerceMailRuntime::processor()->process_due(
            $limit,
            $now,
            null,
            [CommerceMailType::PERSONAL_OFFER, CommerceMailType::MARKETING_CAMPAIGN],
            false
        );
        mtrace(sprintf(
            '[Commerce Mail] limit=%d processed=%d sent=%d retried=%d failed=%d skipped=%d',
            $limit,
            $result['processed'],
            $result['sent'],
            $result['retried'],
            $result['failed'],
            $result['skipped']
        ));
    }
}
