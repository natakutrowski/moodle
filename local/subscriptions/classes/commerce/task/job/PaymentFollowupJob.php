<?php
namespace local_subscriptions\commerce\task\job;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\contract\CommerceTaskJob;
use local_subscriptions\commerce\task\dto\TaskExecutionResult;
use local_subscriptions\commerce\task\repository\PaymentFollowupRepository;
use local_subscriptions\commerce\task\support\TaskLock;
use local_subscriptions\commerce\mail\legacy\CommerceLegacyAutomaticMailPolicy;
use local_subscriptions\mailer;

final class PaymentFollowupJob implements CommerceTaskJob {
    public function __construct(
        private readonly ?PaymentFollowupRepository $repository = null,
        private readonly int $limit = 500
    ) {
    }

    public function run(): TaskExecutionResult {
        $result = new TaskExecutionResult('payment_followup');
        $lock = TaskLock::acquire('payment.followup');

        if (!$lock) {
            $result->mark_locked();
            return $result->finish();
        }

        try {
            $repository = $this->repository ?? new PaymentFollowupRepository();
            $now = time();
            $expireminutes = max(1, (int)(get_config('local_subscriptions', 'expire_pending_after_minutes') ?: 60));
            $reminder1minutes = max(1, (int)(get_config('local_subscriptions', 'reminder1_after_minutes') ?: 1440));
            $reminder2minutes = max($reminder1minutes, (int)(get_config('local_subscriptions', 'reminder2_after_minutes') ?: 4320));
            $expireage = $expireminutes * MINSECS;
            $reminder1age = $reminder1minutes * MINSECS;
            $reminder2age = $reminder2minutes * MINSECS;
            $minimumgap = max(($reminder2minutes - $reminder1minutes) * MINSECS, 5 * MINSECS);

            foreach ($repository->find_pending_to_expire($now - $expireage, $this->limit) as $request) {
                $result->increment('scanned_expirations');
                try {
                    if ($repository->mark_expired_if_pending((int)$request->id, $now)) {
                        $result->increment('expired');
                    } else {
                        $result->increment('skipped');
                    }
                } catch (\Throwable $exception) {
                    $result->add_error((int)$request->id, $exception);
                }
            }

            if (!CommerceLegacyAutomaticMailPolicy::payment_reminders_enabled()) {
                $result->increment('legacy_reminders_disabled');
                return $result->finish();
            }

            foreach ($repository->find_reminder_candidates($this->limit) as $request) {
                $result->increment('scanned_reminders');
                try {
                    $age = $now - (int)$request->creation_date;
                    $stage = (int)$request->reminder_stage;

                    if ($stage === 0 && $age >= $reminder2age) {
                        mailer::dispatch(mailer::T_REMINDER_SECOND, ['pr' => $request]);
                        if ($repository->record_second_reminder((int)$request->id, $now)) {
                            $result->increment('reminder2_sent');
                        } else {
                            $result->increment('skipped');
                        }
                        continue;
                    }

                    if ($stage === 0 && $age >= $reminder1age) {
                        mailer::dispatch(mailer::T_REMINDER_FIRST, ['pr' => $request]);
                        if ($repository->record_first_reminder((int)$request->id, $now)) {
                            $result->increment('reminder1_sent');
                        } else {
                            $result->increment('skipped');
                        }
                        continue;
                    }

                    if ($stage === 1) {
                        $sincefirst = !empty($request->reminder1_at)
                            ? $now - (int)$request->reminder1_at
                            : PHP_INT_MAX;

                        if ($age >= $reminder2age && $sincefirst >= $minimumgap) {
                            mailer::dispatch(mailer::T_REMINDER_SECOND, ['pr' => $request]);
                            if ($repository->record_second_reminder((int)$request->id, $now)) {
                                $result->increment('reminder2_sent');
                            } else {
                                $result->increment('skipped');
                            }
                            continue;
                        }
                    }

                    $result->increment('skipped');
                } catch (\Throwable $exception) {
                    $result->add_error((int)$request->id, $exception);
                }
            }

            return $result->finish();
        } finally {
            $lock->release();
        }
    }
}
