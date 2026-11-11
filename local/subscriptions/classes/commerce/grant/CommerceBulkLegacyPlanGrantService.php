<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\grant;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_manager;
use local_subscriptions\trial\TrialManualCreationGuard;

/**
 * Legacy-plan grants. Never creates a Native purchase, product or entitlement.
 * One immutable campaign-member transaction reference enables safe retries.
 */
final class CommerceBulkLegacyPlanGrantService {
    public const TARGET_LEGACY_PLAN = 'legacy_plan';
    public const TARGET_NATIVE_PRODUCT = 'native_product';

    public function __construct(private readonly \moodle_database $db) {
    }

    /** @return array<string,mixed> Frozen plan/scope/duration/course identity. */
    public function describe(int $planid): array {
        if ($planid <= 0) {
            throw new \invalid_parameter_exception('Legacy plan identifier required.');
        }
        $plan = $this->db->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);
        TrialManualCreationGuard::create()->assert_plan_allowed($planid);
        // A manual grant is not a Trial subscription, even while Trial registration is open.
        if (!empty($plan->is_trial)) {
            throw new \invalid_parameter_exception('Trial plans cannot be granted in bulk.');
        }
        $duration = (string)$plan->duration_key;
        if (!in_array($duration, ['1week', '1month', '3months', '6months', '1year', '3years', 'lifetime'], true)) {
            throw new \invalid_parameter_exception('Unsupported Legacy plan duration.');
        }
        $scopeid = (int)($plan->accessscopeid ?? 0);
        if ($scopeid <= 0 || !$this->db->record_exists('subscription_access_scope', ['id' => $scopeid])) {
            throw new \invalid_parameter_exception('Legacy plan has no valid AccessScope.');
        }
        $entitlements = subscription_manager::get_plan_entitlements($planid);
        $courses = [];
        $rights = [];
        foreach ($entitlements as $entitlement) {
            $courseid = (int)$entitlement->courseid;
            if ($courseid <= 1 || !$this->db->record_exists('course', ['id' => $courseid])) {
                throw new \invalid_parameter_exception('Legacy AccessScope includes a missing course.');
            }
            $courses[] = $courseid;
            $rights[] = [
                'courseid' => $courseid,
                'accesslevel' => (string)($entitlement->accesslevel ?? ''),
                'roleshortname' => (string)($entitlement->roleshortname ?? ''),
                'groupname' => (string)($entitlement->groupname ?? ''),
                'priority' => (int)($entitlement->priority ?? 0),
            ];
        }
        usort($rights, static function(array $a, array $b): int {
            return strcmp(json_encode($a), json_encode($b));
        });
        $courses = array_values(array_unique($courses));
        sort($courses, SORT_NUMERIC);
        if ($courses === []) {
            throw new \invalid_parameter_exception('Legacy plan has no eligible course entitlements.');
        }
        return [
            'type' => self::TARGET_LEGACY_PLAN,
            'id' => $planid,
            'name' => (string)$plan->name,
            'durationkey' => $duration,
            'accessscopeid' => $scopeid,
            'courses' => $courses,
            'rights' => $rights,
            'isactive' => !empty($plan->is_active),
        ];
    }

    /** @return \stdClass|null An existing, unexpired Legacy subscription on this exact Plan. */
    public function existing(int $userid, int $planid, int $now): ?\stdClass {
        $sql = 'SELECT us.* FROM {user_subscription} us
                 WHERE us.userid = :userid AND us.planid = :planid
                   AND us.status IN (:active, :completed)
                   AND us.start_date <= :now
                   AND (us.end_date = 0 OR us.end_date >= :now2)
              ORDER BY us.end_date DESC, us.id DESC';
        $rows = $this->db->get_records_sql($sql, [
            'userid' => $userid, 'planid' => $planid,
            'active' => 'active', 'completed' => 'completed',
            'now' => $now, 'now2' => $now,
        ], 0, 1);
        return $rows ? reset($rows) : null;
    }

    /**
     * Execute one member idempotently. Existing subscriptions are never overwritten.
     * @param array<string,mixed> $frozen Target metadata stored at snapshot time.
     * @return array{state:string,subscriptionid:int,start:int,end:int,courses:array}
     */
    public function grant(int $userid, int $planid, int $campaignid, int $memberid, array $frozen): array {
        $factory = \core\lock\lock_config::get_lock_factory('local_subscriptions_bulk_legacy');
        $lock = $factory->get_lock('plan_' . $planid . '_user_' . $userid, 10);
        if (!$lock) {
            throw new \RuntimeException('Legacy grant is already being processed for this user and Plan.');
        }
        try {
            return $this->grant_locked($userid, $planid, $campaignid, $memberid, $frozen);
        } finally {
            $lock->release();
        }
    }

    /** @param array<string,mixed> $frozen */
    private function grant_locked(int $userid, int $planid, int $campaignid, int $memberid, array $frozen): array {
        if (!$this->db->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new \RuntimeException('The beneficiary account no longer exists.');
        }
        $current = $this->describe($planid);
        // Never silently grant a modified scope, set of courses or duration.
        foreach (['id', 'durationkey', 'accessscopeid', 'courses', 'rights'] as $key) {
            if (($current[$key] ?? null) !== ($frozen[$key] ?? null)) {
                throw new \RuntimeException('Legacy Plan changed since snapshot: rerun after administrative review.');
            }
        }
        $now = time();
        $transaction = 'bulk-legacy-' . $campaignid . '-' . $memberid;
        // A previous attempt may have created the subscription but failed to enrol.
        $own = $this->db->get_record('user_subscription', [
            'userid' => $userid, 'planid' => $planid, 'transactionid' => $transaction,
        ], '*', IGNORE_MISSING);
        if (!$own) {
            if ($this->existing($userid, $planid, $now)) {
                return ['state' => 'skipped', 'subscriptionid' => 0, 'start' => 0, 'end' => 0, 'courses' => []];
            }
            // The historical Legacy helper rejects an old, still-active record even if
            // it is past its expiration. Do not update it or misreport a new grant.
            if ($this->db->record_exists('user_subscription', [
                'userid' => $userid, 'planid' => $planid, 'status' => 'active',
            ])) {
                throw new \RuntimeException('Existing expired-active Legacy subscription needs reconciliation.');
            }
            $end = subscription_manager::get_end_date_from_duration_key($current['durationkey'], $now);
            $created = subscription_manager::create_or_extend_subscription(
                $userid, $planid, 'manual', $transaction, $now, $end,
                0.0, 'EUR', $now, false
            );
            if (($created['status'] ?? '') !== 'created') {
                throw new \RuntimeException('Legacy subscription was not created; check existing subscriptions.');
            }
            $own = $created['subscription'];
        }
        if ((string)$own->status !== 'active') {
            throw new \RuntimeException('Bulk Legacy subscription is not active.');
        }
        subscription_manager::enrol_user_to_courses(
            $userid, $planid, (int)$own->start_date, (int)$own->end_date
        );
        // The historical enrolment helper can log and swallow per-course errors.
        // Verify effective enrolments before recording success.
        foreach ($current['courses'] as $courseid) {
            $sql = 'SELECT ue.id
                      FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.userid = :userid AND e.courseid = :courseid
                       AND e.enrol = :manual AND ue.status = :active
                       AND ue.timestart <= :now
                       AND (ue.timeend = 0 OR ue.timeend >= :now2)';
            if (!$this->db->record_exists_sql($sql, [
                'userid' => $userid, 'courseid' => $courseid,
                'manual' => 'manual', 'active' => 0, 'now' => $now, 'now2' => $now,
            ])) {
                throw new \RuntimeException('Legacy enrolment incomplete for course #' . $courseid . '.');
            }
        }
        return [
            'state' => 'completed',
            'subscriptionid' => (int)$own->id,
            'start' => (int)$own->start_date,
            'end' => (int)$own->end_date,
            'courses' => $current['courses'],
        ];
    }
}
