<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\service\SubscriptionLifecycleService;

/**
 * Applies the global Date B shutdown to every remaining Legacy Trial access.
 *
 * The operation is deliberately idempotent and never deletes/suspends Moodle
 * accounts. It expires active Trial subscriptions, marks active Campus Trial
 * rows expired, and removes residual trialstudent access while preserving any
 * stronger paid/admin access to the same course.
 */
final class TrialLegacyExpiryService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly ?TrialLegacyTransitionPolicy $policy = null,
        private readonly ?TrialAccessRevocationService $access = null,
        private readonly ?SubscriptionLifecycleService $lifecycle = null
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /**
     * @return array{due:bool,dryrun:bool,legacy_expire_at:int|null,subscriptions_expired:int,campus_rows_expired:int,trial_roles_removed:int,enrolments_suspended:int,courses_preserved:int}
     */
    public function expire_due(?int $at = null, bool $dryrun = false): array {
        $at ??= time();
        $policy = $this->policy ?? TrialLegacyTransitionPolicy::create();
        $legacyexpireat = $policy->legacy_expire_at();
        $result = $this->empty_result($dryrun, $legacyexpireat);

        if ($legacyexpireat === null || $at < $legacyexpireat) {
            return $result;
        }
        $result['due'] = true;

        $access = $this->access ?? new TrialAccessRevocationService($this->db);
        $lifecycle = $this->lifecycle ?? new SubscriptionLifecycleService();
        $trialplanids = $this->trial_plan_ids();
        foreach ($this->active_trial_subscriptions($trialplanids) as $subscription) {
            $userid = (int)$subscription->userid;
            if ($dryrun) {
                // Course-role/enrolment impact is counted once below from the
                // current trialstudent assignments to keep the preview exact.
                $result['subscriptions_expired']++;
                continue;
            }

            $this->cap_subscription_end_date($subscription, $legacyexpireat, $at);
            $trialaccess = null;
            if ($lifecycle->expire((int)$subscription->id, $at, $trialaccess)) {
                $result['subscriptions_expired']++;
                if (is_array($trialaccess)) {
                    $this->merge_access_result($result, $trialaccess);
                }
            }
        }

        $this->expire_active_campus_rows($legacyexpireat, $at, $dryrun, $result);

        // Legacy Campus-only accounts and any old residual trialstudent roles
        // still have to lose Trial access at Date B. Course-specific revocation
        // remains conservative and preserves every stronger access path.
        foreach ($this->trial_role_assignments() as $assignment) {
            $userid = (int)$assignment->userid;
            $courseid = (int)$assignment->courseid;
            if ($userid <= 0 || $courseid <= 0) {
                continue;
            }
            $revoked = $access->revoke_user_from_course($userid, $courseid, $at, $dryrun);
            $this->merge_access_result($result, $revoked);
        }

        return $result;
    }

    /** @return int[] */
    private function trial_plan_ids(): array {
        $ids = array_map('intval', $this->db->get_fieldset_select(
            'subscription_plan',
            'id',
            'is_trial = 1'
        ));
        $configured = (int)(get_config('local_subscriptions', 'trial_plan_id') ?: 0);
        if ($configured > 0) {
            $ids[] = $configured;
        }
        $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    /** @return \stdClass[] */
    private function active_trial_subscriptions(array $trialplanids): array {
        if ($trialplanids === []) {
            return [];
        }
        [$insql, $params] = $this->db->get_in_or_equal($trialplanids, SQL_PARAMS_NAMED, 'trialplan');
        $params['active'] = Status::ACTIVE;
        return array_values($this->db->get_records_select(
            'user_subscription',
            "status = :active AND planid {$insql}",
            $params,
            'id ASC'
        ));
    }

    private function cap_subscription_end_date(\stdClass $subscription, int $legacyexpireat, int $at): void {
        $enddate = (int)($subscription->end_date ?? 0);
        if ($enddate === 0 || $enddate > $legacyexpireat) {
            $subscription->end_date = $legacyexpireat;
            $subscription->last_update = $at;
            $this->db->update_record('user_subscription', $subscription);
        }
    }

    /**
     * @param array<string,mixed> $result
     * @return int[] user ids represented by Campus Trial rows
     */
    private function expire_active_campus_rows(
        int $legacyexpireat,
        int $at,
        bool $dryrun,
        array &$result
    ): array {
        $table = new \xmldb_table('local_campus_trial');
        if (!$this->db->get_manager()->table_exists($table)) {
            return [];
        }

        $userids = [];
        foreach ($this->db->get_records('local_campus_trial', ['status' => 1], 'id ASC') as $trial) {
            $userid = (int)($trial->userid ?? 0);
            if ($userid > 0) {
                $userids[$userid] = $userid;
            }
            $result['campus_rows_expired']++;
            if ($dryrun) {
                continue;
            }

            $trial->status = 2;
            $expiresat = (int)($trial->expiresat ?? 0);
            if ($expiresat === 0 || $expiresat > $legacyexpireat) {
                $trial->expiresat = $legacyexpireat;
            }
            $this->db->update_record('local_campus_trial', $trial);
        }

        return array_values($userids);
    }

    /** @return \stdClass[] */
    private function trial_role_assignments(): array {
        $trialroleid = (int)$this->db->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($trialroleid <= 0) {
            return [];
        }

        $sql = 'SELECT ra.id, ra.userid, ctx.instanceid AS courseid
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE ra.roleid = :roleid
                   AND ctx.contextlevel = :courselevel
              ORDER BY ra.id ASC';
        return array_values($this->db->get_records_sql($sql, [
            'roleid' => $trialroleid,
            'courselevel' => CONTEXT_COURSE,
        ]));
    }

    /** @param array<string,mixed> $result */
    private function merge_access_result(array &$result, array $access): void {
        $result['trial_roles_removed'] += (int)($access['roles_removed'] ?? 0);
        $result['enrolments_suspended'] += (int)($access['enrolments_suspended'] ?? 0);
        $result['courses_preserved'] += (int)($access['courses_preserved'] ?? 0);
    }

    /**
     * @return array{due:bool,dryrun:bool,legacy_expire_at:int|null,subscriptions_expired:int,campus_rows_expired:int,trial_roles_removed:int,enrolments_suspended:int,courses_preserved:int}
     */
    private function empty_result(bool $dryrun, ?int $legacyexpireat): array {
        return [
            'due' => false,
            'dryrun' => $dryrun,
            'legacy_expire_at' => $legacyexpireat,
            'subscriptions_expired' => 0,
            'campus_rows_expired' => 0,
            'trial_roles_removed' => 0,
            'enrolments_suspended' => 0,
            'courses_preserved' => 0,
        ];
    }
}
