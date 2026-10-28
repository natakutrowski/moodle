<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCourseCampaignGate;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignContentPolicy;
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
        $optouts = CommerceMarketingOptoutService::create($DB);

        foreach ($repository->due($now, 10) as $campaign) {
            if ((string)($campaign->audiencesource ?? 'manual') === 'course') {
                if (!CommerceMarketingCourseCampaignGate::enabled()) {
                    mtrace('[Marketing Mail] course campaign release switch OFF: ' . (int)$campaign->id);
                    continue;
                }
                // Check that no one lost eligibility since the operator confirmed the audience.
                // An unsafe snapshot is paused rather than partly sent.
                if (!CommerceMarketingCourseCampaignGate::snapshot_is_current($DB, $repository, $campaign)) {
                    $repository->mark_paused((int)$campaign->id, time());
                    mtrace('[Marketing Mail] course campaign paused: stale recipients ' . (int)$campaign->id);
                    continue;
                }
            }
            $queued = 0;
            $contents = $repository->contents((int)$campaign->id);
            foreach ($repository->recipients((int)$campaign->id) as $recipient) {
                // Operators can pause or cancel a campaign while this cron is running.
                // Re-check state before queuing every recipient.
                if ((string)$repository->get((int)$campaign->id)->status !== 'scheduled') {
                    break;
                }
                if (!empty($recipient->mailid) || (string)$recipient->status === 'cancelled') {
                    continue;
                }
                if ($optouts->is_suppressed((string)$recipient->email)) {
                    $repository->mark_recipient_suppressed((int)$recipient->id, $now);
                    continue;
                }
                if (CommerceMarketingCampaignContentPolicy::select(
                    $contents, (string)$recipient->language
                ) === null) {
                    $repository->mark_recipient_suppressed((int)$recipient->id, $now);
                    mtrace(sprintf('[Marketing Mail] campaign=%d recipient=%d cancelled: incompatible language %s',
                        (int)$campaign->id, (int)$recipient->id, (string)$recipient->language));
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

            $current = $repository->get((int)$campaign->id);
            if ((string)$current->status === 'scheduled') {
                $repository->mark_queued((int)$campaign->id, $now);
            } else if ((string)$current->status === 'cancelled') {
                $repository->cancel_pending((int)$campaign->id, $now);
            }
            mtrace(sprintf(
                '[Marketing Mail] campaign=%d queued=%d',
                (int)$campaign->id,
                $queued
            ));
        }
    }
}
