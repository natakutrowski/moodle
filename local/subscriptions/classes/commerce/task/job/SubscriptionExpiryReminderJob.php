<?php

namespace local_subscriptions\commerce\task\job;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\SubscriptionReminderRepository;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\commerce\mail\legacy\CommerceLegacyAutomaticMailPolicy;
use local_subscriptions\mailer;
final class SubscriptionExpiryReminderJob implements CommerceTaskJob {
    public function __construct(private readonly ?SubscriptionReminderRepository $repository = null) {
    }
    public function run(): TaskExecutionResult {
        $r = new TaskExecutionResult('subscription_expiry_reminder');
        $lock = TaskLock::acquire('subscription.expiry_reminder');
        if (!$lock) {
            $r->mark_locked();
            return $r->finish();
        }
        try {
            if (!CommerceLegacyAutomaticMailPolicy::expiry_reminders_enabled()) {
                $r->increment('legacy_reminders_disabled');
                return $r->finish();
            }

            $repo = $this->repository ?? new SubscriptionReminderRepository();
            $now = time();
            $global = $this->parse((string)(get_config('local_subscriptions', 'expiry_reminder_days') ?? '7'));
            if (!$global) {
                $global = [7];
            }
            foreach ($repo->find_candidates($now) as $s) {
                $r->increment('scanned');
                try {
                    if (isset($s->expiry_reminder_enabled) && (int)$s->expiry_reminder_enabled === 0) {
                        $r->increment('skipped');
                        continue;
                    }
                    $days = (int)ceil(((int)$s->end_date - $now) / DAYSECS);
                    $dayslist = $this->parse((string)($s->expiry_reminder_days ?? '')) ?: $global;
                    if ($days <= 0 || !in_array($days, $dayslist, true) || $repo->has_queued_in_scope($s)) {
                        $r->increment('skipped');
                        continue;
                    }
                    $new = 'J-'.$days;
                    $old = 'd'.$days;
                    if ($repo->reminder_exists((int)$s->id, $new, $old)) {
                        $r->increment('skipped');
                        continue;
                    }
                    $user = $repo->user((int)$s->userid);
                    $plan = $repo->plan((int)$s->planid);
                    if (!$user || !$plan) {
                        throw new \RuntimeException('Reminder context is incomplete.');
                    }
                    mailer::dispatch(mailer::T_SUBSCRIPTION_EXPIRY_REM, ['user' => $user, 'plan' => $plan, 'sub' => $s, 'days' => $days]);
                    $repo->record_sent($s, $new, $now);
                    $r->increment('sent');
                } catch (\Throwable $e) {
                    $r->add_error((int)$s->id, $e);
                }
            }
            return $r->finish();
        } finally {
            $lock->release();
        }
    }
    private function parse(string $csv): array {
        $arr = array_values(array_unique(array_filter(array_map('intval', preg_split('/[,\s;]+/', trim($csv))))));
        $arr = array_filter($arr, static fn($d) => $d >= 0 && $d <= 365);
        sort($arr);
        return $arr;
    }
}
