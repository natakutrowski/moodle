<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

final class process_marketing_mail_queue_task extends scheduled_task {
    public function get_name(): string {
        return get_string('task_process_marketing_mail_queue', 'local_subscriptions');
    }

    public function execute(): void {
        $now = time();

        if ((string)get_config('local_subscriptions', 'commerce_mail_marketing_enabled') === '0') {
            mtrace('[Marketing Mail] worker disabled.');
            return;
        }

        $batch = max(1, min(500, (int)(
            get_config('local_subscriptions', 'commerce_mail_marketing_batch_size') ?: 50
        )));
        $hourly = max(0, min(5000, (int)(
            get_config('local_subscriptions', 'commerce_mail_marketing_hourly_limit') ?: 250
        )));
        $globalhourly = max(0, min(10000, (int)(
            get_config('local_subscriptions', 'commerce_mail_global_hourly_limit') ?: 0
        )));

        $repository = new CommerceMailQueueRepository();
        $limit = $batch;

        if ($hourly > 0) {
            $limit = min(
                $limit,
                max(0, $hourly - $repository->count_marketing_sent_since($now - HOURSECS))
            );
        }
        if ($globalhourly > 0) {
            $limit = min(
                $limit,
                max(0, $globalhourly - $repository->count_all_sent_since($now - HOURSECS))
            );
        }

        if ($limit <= 0) {
            mtrace('[Marketing Mail] throttled by SMTP limits.');
            return;
        }

        $result = CommerceMailRuntime::processor()->process_due(
            $limit,
            $now,
            [CommerceMailType::MARKETING_CAMPAIGN],
            [],
            false
        );
        mtrace(sprintf(
            '[Marketing Mail] limit=%d processed=%d sent=%d retried=%d failed=%d',
            $limit,
            $result['processed'],
            $result['sent'],
            $result['retried'],
            $result['failed']
        ));

        global $DB;
        $campaigns = $DB->get_records('local_subs_mail_campaign', ['status' => 'queued']);
        foreach ($campaigns as $campaign) {
            $pending = (int)$DB->count_records_sql(
                'SELECT COUNT(1)
                   FROM {local_subs_mail_campaign_recipient} r
                   JOIN {local_subs_commerce_mail} m ON m.id = r.mailid
                  WHERE r.campaignid = :campaignid
                    AND m.status IN (:queued, :processing)',
                [
                    'campaignid' => (int)$campaign->id,
                    'queued' => 'queued',
                    'processing' => 'processing',
                ]
            );
            if ($pending === 0) {
                $DB->update_record('local_subs_mail_campaign', (object)[
                    'id' => (int)$campaign->id,
                    'status' => 'completed',
                    'completedat' => $now,
                    'timemodified' => $now,
                ]);
            }
        }
    }
}
