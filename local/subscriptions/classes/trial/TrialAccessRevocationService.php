<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\subscription_manager;

/**
 * Safely revokes Trial-only course access while preserving every stronger access path.
 *
 * Trial and paid Commerce currently share Moodle manual enrolment instances. Suspending
 * every enrolment in a Trial scope would therefore also cut access purchased later.
 * This service removes only the Trial role and suspends the manual enrolment when no
 * stronger Moodle role, Native Commerce grant or non-Trial subscription still justifies
 * access to that course.
 */
final class TrialAccessRevocationService {
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /**
     * @return array{courses:int,roles_removed:int,enrolments_suspended:int,courses_preserved:int}
     */
    public function revoke_user_from_plan(int $userid, int $planid, ?int $at = null, bool $dryrun = false): array {
        $result = $this->empty_result();
        if ($userid <= 0 || $planid <= 0) {
            return $result;
        }

        foreach ($this->course_ids_for_plan($planid) as $courseid) {
            $course = $this->revoke_user_from_course($userid, $courseid, $at, $dryrun);
            foreach ($result as $key => $value) {
                $result[$key] += $course[$key];
            }
        }

        return $result;
    }

    /**
     * @return array{courses:int,roles_removed:int,enrolments_suspended:int,courses_preserved:int}
     */
    public function revoke_user_from_course(int $userid, int $courseid, ?int $at = null, bool $dryrun = false): array {
        global $CFG;

        $result = $this->empty_result();
        if ($userid <= 0 || $courseid <= 0) {
            return $result;
        }

        $at ??= time();
        $trialroleid = (int)$this->db->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($trialroleid <= 0) {
            return $result;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return $result;
        }

        $trialassignments = array_values($this->db->get_records('role_assignments', [
            'userid' => $userid,
            'roleid' => $trialroleid,
            'contextid' => $context->id,
        ]));
        if ($trialassignments === []) {
            return $result;
        }

        $result['courses'] = 1;
        $preserve = $this->has_stronger_access($userid, $courseid, $context->id, $trialroleid, $at);

        if (!$dryrun) {
            require_once($CFG->dirroot . '/lib/accesslib.php');
            foreach ($trialassignments as $assignment) {
                role_unassign(
                    $trialroleid,
                    $userid,
                    $context->id,
                    (string)($assignment->component ?? ''),
                    (int)($assignment->itemid ?? 0)
                );
            }
        }
        $result['roles_removed'] = count($trialassignments);

        if ($preserve) {
            $result['courses_preserved'] = 1;
            return $result;
        }

        $result['enrolments_suspended'] = $this->suspend_manual_enrolments($userid, $courseid, $dryrun);
        return $result;
    }

    /** @return int[] */
    public function course_ids_for_plan(int $planid): array {
        $courseids = [];

        foreach ($this->db->get_records('subscription_plan_entitlement', ['planid' => $planid]) as $entitlement) {
            $courseid = (int)($entitlement->courseid ?? 0);
            if ($courseid > 0) {
                $courseids[$courseid] = $courseid;
            }
        }

        if ($courseids !== []) {
            ksort($courseids, SORT_NUMERIC);
            return array_values($courseids);
        }

        $scope = subscription_manager::get_access_scope_from_planid($planid);
        if (!$scope || empty($scope->course_ids)) {
            return [];
        }

        foreach ($this->parse_course_ids((string)$scope->course_ids) as $courseid) {
            $courseids[$courseid] = $courseid;
        }
        ksort($courseids, SORT_NUMERIC);
        return array_values($courseids);
    }

    private function has_stronger_access(
        int $userid,
        int $courseid,
        int $contextid,
        int $trialroleid,
        int $at
    ): bool {
        if ($this->db->record_exists_select(
            'role_assignments',
            'userid = :userid AND contextid = :contextid AND roleid <> :trialroleid',
            [
                'userid' => $userid,
                'contextid' => $contextid,
                'trialroleid' => $trialroleid,
            ]
        )) {
            return true;
        }

        if ($this->has_effective_native_course_grant($userid, $courseid, $at)) {
            return true;
        }

        return $this->has_effective_nontrial_subscription($userid, $courseid, $at);
    }

    private function has_effective_native_course_grant(int $userid, int $courseid, int $at): bool {
        $table = new \xmldb_table('local_subs_commerce_grant');
        if (!$this->db->get_manager()->table_exists($table)) {
            return false;
        }

        $sql = 'SELECT 1
                  FROM {local_subs_commerce_grant}
                 WHERE beneficiaryuserid = :userid
                   AND type = :type
                   AND resourcekey LIKE :resourcekey
                   AND status IN (:active, :granted, :completed)
                   AND validfrom <= :at
                   AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :at2)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':%',
            'active' => 'active',
            'granted' => 'granted',
            'completed' => 'completed',
            'at' => $at,
            'at2' => $at,
        ]);
    }

    private function has_effective_nontrial_subscription(int $userid, int $courseid, int $at): bool {
        $sql = 'SELECT us.id, s.course_ids
                  FROM {user_subscription} us
                  JOIN {subscription_plan} p ON p.id = us.planid
                  JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
                 WHERE us.userid = :userid
                   AND (p.is_trial IS NULL OR p.is_trial = 0)
                   AND us.status IN (:active, :completed)
                   AND us.start_date <= :at
                   AND (us.end_date = 0 OR us.end_date >= :at2)';

        $records = $this->db->get_records_sql($sql, [
            'userid' => $userid,
            'active' => Status::ACTIVE,
            'completed' => Status::COMPLETED,
            'at' => $at,
            'at2' => $at,
        ]);

        foreach ($records as $record) {
            if (in_array($courseid, $this->parse_course_ids((string)$record->course_ids), true)) {
                return true;
            }
        }

        return false;
    }

    private function suspend_manual_enrolments(int $userid, int $courseid, bool $dryrun): int {
        $manual = enrol_get_plugin('manual');
        if (!$manual) {
            return 0;
        }

        $count = 0;
        foreach (enrol_get_instances($courseid, true) as $instance) {
            if ($instance->enrol !== 'manual') {
                continue;
            }

            $userenrolment = $this->db->get_record('user_enrolments', [
                'enrolid' => $instance->id,
                'userid' => $userid,
            ], 'id,status', IGNORE_MISSING);
            if (!$userenrolment || (int)$userenrolment->status === ENROL_USER_SUSPENDED) {
                continue;
            }

            $count++;
            if (!$dryrun) {
                $manual->update_user_enrol($instance, $userid, ENROL_USER_SUSPENDED);
            }
        }

        return $count;
    }

    /** @return int[] */
    private function parse_course_ids(string $value): array {
        $decoded = json_decode($value, true);
        $values = is_array($decoded)
            ? $decoded
            : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $values ?: []),
            static fn(int $id): bool => $id > 0
        )));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    /** @return array{courses:int,roles_removed:int,enrolments_suspended:int,courses_preserved:int} */
    private function empty_result(): array {
        return [
            'courses' => 0,
            'roles_removed' => 0,
            'enrolments_suspended' => 0,
            'courses_preserved' => 0,
        ];
    }
}
