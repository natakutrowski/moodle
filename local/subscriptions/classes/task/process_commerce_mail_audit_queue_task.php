<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;

/** Low-priority throttled worker for Commerce audit copies. */
final class process_commerce_mail_audit_queue_task extends scheduled_task {
    public function get_name(): string {
        return get_string('task_process_commerce_mail_audit_queue', 'local_subscriptions');
    }

    public function execute(): void {
        $now = time();

        if ((string)get_config('local_subscriptions', 'commerce_mail_audit_enabled') === '0') {
            mtrace('[Commerce Mail Audit] disabled by Commerce CRM configuration.');
            return;
        }

        $batch = max(1, min(200, (int)(get_config('local_subscriptions', 'commerce_mail_audit_batch_size') ?: 10)));
        $hourly = max(1, min(2000, (int)(get_config('local_subscriptions', 'commerce_mail_audit_hourly_limit') ?: 50)));

        $repository = new CommerceMailQueueRepository();

        // Audit copies never compete with customer or campaign delivery.
        if ($repository->has_due_non_audit($now)) {
            mtrace('[Commerce Mail Audit] deferred: higher-priority mail is waiting.');
            return;
        }

        $sentlasthour = $repository->count_audit_sent_since($now - HOURSECS);
        $remaining = max(0, $hourly - $sentlasthour);

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
        if ($globalhourly > 0) {
            $remaining = min(
                $remaining,
                max(
                    0,
                    $globalhourly - $repository->count_all_sent_since($now - HOURSECS)
                )
            );
        }

        if ($remaining <= 0) {
            mtrace(sprintf(
                '[Commerce Mail Audit] throttled hourly_limit=%d sent_last_hour=%d',
                $hourly,
                $sentlasthour
            ));
            return;
        }

        $limit = min($batch, $remaining);
        $repository->recover_stale_processing($now - 1800, $now);

        $result = CommerceMailRuntime::processor()->process_due(
            $limit,
            $now,
            null,
            [],
            true
        );

        mtrace(sprintf(
            '[Commerce Mail Audit] limit=%d hourly_limit=%d sent_last_hour=%d processed=%d sent=%d retried=%d failed=%d skipped=%d',
            $limit,
            $hourly,
            $sentlasthour,
            $result['processed'],
            $result['sent'],
            $result['retried'],
            $result['failed'],
            $result['skipped']
        ));
    }
}
