<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\commerce\mail\legacy\CommerceLegacyAutomaticMailPolicy;
use local_subscriptions\mailer;
use local_subscriptions\subscription_manager;

final class SubscriptionLifecycleService {

    public function activate(int $subscriptionid, int $now): bool {
        global $DB;

        $subscription = $DB->get_record(
            'user_subscription',
            [
                'id' => $subscriptionid,
                'status' => Status::QUEUED,
            ],
            '*',
            IGNORE_MISSING,
        );

        if (!$subscription) {
            return false;
        }

        $user = $DB->get_record(
            'user',
            [
                'id' => (int) $subscription->userid,
                'deleted' => 0,
            ],
            '*',
            IGNORE_MISSING,
        );
        $plan = $DB->get_record(
            'subscription_plan',
            ['id' => (int) $subscription->planid],
            '*',
            IGNORE_MISSING,
        );

        if (!$user || !$plan) {
            throw new \RuntimeException('Activation context is incomplete.');
        }

        subscription_manager::enrol_user_to_courses(
            (int) $subscription->userid,
            (int) $subscription->planid,
            (int) $subscription->start_date,
            (int) $subscription->end_date,
        );

        $updated = $DB->set_field_select(
            'user_subscription',
            'status',
            Status::ACTIVE,
            'id = :id AND status = :queued',
            [
                'id' => (int) $subscription->id,
                'queued' => Status::QUEUED,
            ],
        );

        if (!$updated) {
            return false;
        }

        $DB->set_field(
            'user_subscription',
            'last_update',
            $now,
            ['id' => (int) $subscription->id],
        );

        $subscription->status = Status::ACTIVE;
        $subscription->last_update = $now;

        if (CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()) {
            try {
                mailer::dispatch(
                    mailer::T_SUBSCRIPTION_ACTIVATED,
                    [
                        'user' => $user,
                        'plan' => $plan,
                        'sub' => $subscription,
                    ],
                );
            } catch (\Throwable $exception) {
                debugging(
                    '[local_subscriptions][lifecycle] Activation email failed for subscription #'
                        . (int) $subscription->id . ': ' . $exception->getMessage(),
                    DEBUG_DEVELOPER,
                );
            }
        }

        return true;
    }

    public function expire(int $subscriptionid, int $now): bool {
        global $DB;

        $subscription = $DB->get_record(
            'user_subscription',
            [
                'id' => $subscriptionid,
                'status' => Status::ACTIVE,
            ],
            '*',
            IGNORE_MISSING,
        );

        if (!$subscription) {
            return false;
        }

        $plan = $DB->get_record(
            'subscription_plan',
            ['id' => (int) $subscription->planid],
            '*',
            IGNORE_MISSING,
        );

        if (!$plan) {
            throw new \RuntimeException('Expiration plan is missing.');
        }

        subscription_manager::suspend_user_in_plan_courses(
            (int) $subscription->userid,
            (int) $subscription->planid,
        );

        $updated = $DB->set_field_select(
            'user_subscription',
            'status',
            Status::EXPIRED,
            'id = :id AND status = :active',
            [
                'id' => (int) $subscription->id,
                'active' => Status::ACTIVE,
            ],
        );

        if (!$updated) {
            return false;
        }

        $DB->set_field(
            'user_subscription',
            'last_update',
            $now,
            ['id' => (int) $subscription->id],
        );

        $subscription->status = Status::EXPIRED;
        $subscription->last_update = $now;

        if (!empty($plan->is_trial) || $this->has_next_subscription_in_scope($subscription, $plan, $now)) {
            return true;
        }

        $user = $DB->get_record(
            'user',
            [
                'id' => (int) $subscription->userid,
                'deleted' => 0,
            ],
            '*',
            IGNORE_MISSING,
        );

        if ($user && CommerceLegacyAutomaticMailPolicy::lifecycle_emails_enabled()) {
            try {
                mailer::dispatch(
                    mailer::T_SUBSCRIPTION_EXPIRED,
                    [
                        'user' => $user,
                        'plan' => $plan,
                        'sub' => $subscription,
                    ],
                );
            } catch (\Throwable $exception) {
                debugging(
                    '[local_subscriptions][lifecycle] Expiration email failed for subscription #'
                        . (int) $subscription->id . ': ' . $exception->getMessage(),
                    DEBUG_DEVELOPER,
                );
            }
        }

        return true;
    }

    private function has_next_subscription_in_scope(
        \stdClass $subscription,
        \stdClass $plan,
        int $now,
    ): bool {
        global $DB;

        $scopeid = (int) ($plan->accessscopeid ?? 0);

        if ($scopeid <= 0) {
            return $DB->record_exists_select(
                'user_subscription',
                'userid = :userid
                     AND planid = :planid
                     AND status = :queued
                     AND start_date > :now',
                [
                    'userid' => (int) $subscription->userid,
                    'planid' => (int) $subscription->planid,
                    'queued' => Status::QUEUED,
                    'now' => $now,
                ],
            );
        }

        $sql = "SELECT 1
                  FROM {user_subscription} ns
                  JOIN {subscription_plan} np
                    ON np.id = ns.planid
                 WHERE ns.userid = :userid
                   AND ns.status = :queued
                   AND ns.start_date > :now
                   AND np.accessscopeid = :scopeid";

        return $DB->record_exists_sql(
            $sql,
            [
                'userid' => (int) $subscription->userid,
                'queued' => Status::QUEUED,
                'now' => $now,
                'scopeid' => $scopeid,
            ],
        );
    }
}
