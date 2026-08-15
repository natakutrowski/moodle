<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

final class process_marketing_mail_campaigns_task extends scheduled_task {
    public function get_name(): string {
        return get_string('task_process_marketing_mail_campaigns', 'local_subscriptions');
    }

    public function execute(): void {
        global $DB;

        if ((string)get_config('local_subscriptions', 'commerce_mail_marketing_enabled') === '0') {
            mtrace('[Marketing Mail] campaign dispatcher disabled.');
            return;
        }

        $now = time();
        $repository = new CommerceMarketingCampaignRepository($DB);
        $queue = CommerceMailRuntime::queue_service();

        foreach ($repository->due($now, 10) as $campaign) {
            $queued = 0;
            foreach ($repository->recipients((int)$campaign->id) as $recipient) {
                if (!empty($recipient->mailid)) {
                    continue;
                }
                $fullname = trim((string)$recipient->firstname . ' ' . (string)$recipient->lastname);
                $mail = $queue->queue(new CommerceMailRequest(
                    CommerceMailType::MARKETING_CAMPAIGN,
                    new CommerceMailRecipient(
                        (string)$recipient->email,
                        $fullname,
                        $recipient->userid === null ? null : (int)$recipient->userid
                    ),
                    new CommerceMailContext([
                        'campaignid' => (int)$campaign->id,
                        'firstname' => (string)$recipient->firstname,
                        'lastname' => (string)$recipient->lastname,
                        'fullname' => $fullname,
                    ]),
                    (string)$recipient->language,
                    CommerceMailIdempotencyKey::normalise(
                        'marketing:' . (int)$campaign->id . ':recipient:' . (int)$recipient->id
                    )
                ));
                $repository->mark_recipient_queued(
                    (int)$recipient->id,
                    (int)$mail->id,
                    $now
                );
                $queued++;
            }

            $repository->mark_queued((int)$campaign->id, $now);
            mtrace(sprintf(
                '[Marketing Mail] campaign=%d queued=%d',
                (int)$campaign->id,
                $queued
            ));
        }
    }
}
