<?php
namespace local_campus\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy Campus Trial mirror maintenance.
 *
 * 7.98D5: the Native subscription lifecycle is authoritative. This task only
 * keeps local_campus_trial aligned for Legacy UX/history and delegates access
 * revocation to local_subscriptions. It never suspends or deletes Moodle users.
 */
class trial_maint_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('cron_trial_maint', 'local_campus');
    }

    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/subscriptions/classes/trial_manager.php');

        $now = time();
        $transitionpolicy = \local_subscriptions\trial\TrialLegacyTransitionPolicy::create();

        // Date B is a global hard stop and remains idempotent.
        if ($transitionpolicy->legacy_window_is_closed($now)) {
            $legacyexpiry = \local_subscriptions\trial\TrialLegacyExpiryService::create()
                ->expire_due($now);
            mtrace(sprintf(
                'trial_maint_task: Date B closed; subscriptions=%d roles=%d enrolments=%d preserved=%d',
                (int)$legacyexpiry['subscriptions_expired'],
                (int)$legacyexpiry['trial_roles_removed'],
                (int)$legacyexpiry['enrolments_suspended'],
                (int)$legacyexpiry['courses_preserved']
            ));
            return true;
        }

        $trialplanid = \local_subscriptions\trial_manager::get_trial_planid();
        if ($trialplanid <= 0) {
            mtrace('trial_maint_task: no Native Trial plan configured');
            return true;
        }

        $trials = $DB->get_records('local_campus_trial', null, '', '*');
        if (!$trials) {
            return true;
        }

        $lifecycle = new \local_subscriptions\service\SubscriptionLifecycleService();

        foreach ($trials as $trial) {
            if (empty($trial->userid)) {
                continue;
            }
            $userid = (int)$trial->userid;

            // A paid/non-Trial subscription makes the Legacy row historical.
            $hasnontrial = $DB->record_exists_sql(
                'SELECT 1
                   FROM {user_subscription} s
                   JOIN {subscription_plan} p ON p.id = s.planid
                  WHERE s.userid = :userid
                    AND (p.is_trial IS NULL OR p.is_trial = 0)',
                ['userid' => $userid]
            );
            if ($hasnontrial) {
                if ((int)$trial->status !== 3) {
                    $trial->status = 3;
                    $DB->update_record('local_campus_trial', $trial);
                }
                continue;
            }

            $subscriptions = $DB->get_records(
                'user_subscription',
                ['userid' => $userid, 'planid' => $trialplanid],
                'id DESC',
                '*',
                0,
                20
            );

            // Historical Campus-only rows are preserved as history. D5 does
            // not manufacture Native subscriptions after the acquisition era.
            if (!$subscriptions) {
                continue;
            }

            // Prefer an active Native Trial if one exists; otherwise the most
            // recent row is sufficient to keep the Legacy mirror historical.
            $subscription = reset($subscriptions);
            foreach ($subscriptions as $candidate) {
                if ((string)$candidate->status === \local_subscriptions\constants\Status::ACTIVE) {
                    $subscription = $candidate;
                    break;
                }
            }

            // Keep the Legacy mirror aligned with the Native end date.
            if ((int)$trial->expiresat !== (int)$subscription->end_date) {
                $trial->expiresat = (int)$subscription->end_date;
                $DB->update_record('local_campus_trial', $trial);
            }

            $status = (string)$subscription->status;
            if ($status === \local_subscriptions\constants\Status::REPLACED) {
                if ((int)$trial->status !== 3) {
                    $trial->status = 3;
                    $DB->update_record('local_campus_trial', $trial);
                }
                continue;
            }

            $isexpired = (int)$subscription->end_date > 0 && $now >= (int)$subscription->end_date;
            if (!$isexpired && $status === \local_subscriptions\constants\Status::ACTIVE) {
                continue;
            }

            if ($isexpired && $status === \local_subscriptions\constants\Status::ACTIVE) {
                $trialaccess = null;
                $lifecycle->expire((int)$subscription->id, $now, $trialaccess);
                $status = \local_subscriptions\constants\Status::EXPIRED;
            }

            if ($status === \local_subscriptions\constants\Status::EXPIRED && (int)$trial->status !== 2) {
                $trial->status = 2;
                if (empty($trial->reminder7_sent)) {
                    $trial->reminder7_sent = $now;
                }
                $DB->update_record('local_campus_trial', $trial);
            }

            if (function_exists('local_campus_clear_cookie')) {
                local_campus_clear_cookie();
            }
        }

        return true;
    }
}
