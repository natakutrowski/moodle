<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/** Throttled Personal Offer campaign mail worker. */
final class process_personal_offer_mail_queue_task extends scheduled_task {
    public function get_name(): string {
        return get_string('task_process_personal_offer_mail_queue', 'local_subscriptions');
    }

    public function execute(): void {
        $now = time();
        $batch = max(1, min(500, (int)(get_config('local_subscriptions', 'personal_offer_mail_batch_size') ?: 20)));
        $hourly = max(1, min(5000, (int)(get_config('local_subscriptions', 'personal_offer_mail_hourly_limit') ?: 100)));
        $repository = new CommerceMailQueueRepository();
        $sentlasthour = $repository->count_sent_since(CommerceMailType::PERSONAL_OFFER, $now - HOURSECS);
        $remaining = max(0, $hourly - $sentlasthour);
        if ($remaining <= 0) {
            mtrace(sprintf('[Personal Offer Mail] throttled hourly_limit=%d sent_last_hour=%d', $hourly, $sentlasthour));
            return;
        }
        $limit = min($batch, $remaining);
        $repository->recover_stale_processing($now - 1800, $now);
        $result = CommerceMailRuntime::processor()->process_due($limit, $now, [CommerceMailType::PERSONAL_OFFER], [], false);
        mtrace(sprintf(
            '[Personal Offer Mail] limit=%d hourly_limit=%d sent_last_hour=%d processed=%d sent=%d retried=%d failed=%d cancelled=%d skipped=%d',
            $limit, $hourly, $sentlasthour, $result['processed'], $result['sent'], $result['retried'], $result['failed'], $result['cancelled'], $result['skipped']
        ));
    }
}
