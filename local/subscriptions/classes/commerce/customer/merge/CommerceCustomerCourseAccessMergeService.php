<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Normalises course access when two Moodle identities are merged.
 *
 * Managed learner-role precedence is intentionally aligned with the existing
 * fulfillment rules:
 *
 * student > grammarstudent > trialstudent
 *
 * Interval rule:
 * - different access levels: keep the interval belonging to the highest level;
 * - same access level: take the union of both intervals.
 *
 * Only courses where BOTH users have a recognised managed learner role are
 * normalised. The enrolment plugin is deliberately not part of the access-level
 * decision because paid and Trial access may have been created by different
 * historical mechanisms. Unrelated/custom roles remain untouched.
 */
final class CommerceCustomerCourseAccessMergeService {
    private const ROLE_RANK = [
        'trialstudent' => 10,
        'grammarstudent' => 20,
        'student' => 30,
    ];

    public function __construct(private readonly moodle_database $database) {
    }

    /**
     * Snapshot the shared-course access decision before any rows are moved.
     *
     * @return array<int,array<string,mixed>>
     */
    public function plan(int $sourceuserid, int $targetuserid): array {
        $source = $this->access_by_course($sourceuserid);
        $target = $this->access_by_course($targetuserid);
        $plans = [];

        foreach (array_intersect(array_keys($source), array_keys($target)) as $courseid) {
            $sourceaccess = $source[$courseid];
            $targetaccess = $target[$courseid];

            // Be conservative: normalise only when both sides have an explicit
            // CampusFR-managed learner role.
            if ($sourceaccess['role'] === null || $targetaccess['role'] === null) {
                continue;
            }

            $sourcerank = self::ROLE_RANK[$sourceaccess['role']] ?? 0;
            $targetrank = self::ROLE_RANK[$targetaccess['role']] ?? 0;

            if ($sourcerank > $targetrank) {
                $winner = 'source';
                $final = $sourceaccess;
            } else if ($targetrank > $sourcerank) {
                $winner = 'target';
                $final = $targetaccess;
            } else {
                $winner = 'union';
                $final = $this->union_access($sourceaccess, $targetaccess);
            }

            $plans[(int)$courseid] = [
                'courseid' => (int)$courseid,
                'sourceuserid' => $sourceuserid,
                'targetuserid' => $targetuserid,
                'sourcerole' => $sourceaccess['role'],
                'targetrole' => $targetaccess['role'],
                'finalrole' => $final['role'],
                'winner' => $winner,
                'timestart' => (int)$final['timestart'],
                'timeend' => (int)$final['timeend'],
                'status' => (int)$final['status'],
                'enrolids' => array_values(array_unique(array_merge(
                    $sourceaccess['enrolids'],
                    $targetaccess['enrolids']
                ))),
            ];
        }

        return $plans;
    }

    /**
     * Apply the already snapshotted decision after enrolment/role rows were merged.
     *
     * @return array{courses:int,rolesremoved:int,rolesdeduplicated:int,enrolmentsnormalised:int}
     */
    public function apply(int $targetuserid, array $plans): array {
        global $CFG;

        require_once($CFG->dirroot . '/lib/accesslib.php');

        $result = [
            'courses' => 0,
            'rolesremoved' => 0,
            'rolesdeduplicated' => 0,
            'enrolmentsnormalised' => 0,
        ];
        $upgradedfromtrial = false;

        foreach ($plans as $plan) {
            $courseid = (int)($plan['courseid'] ?? 0);
            $finalrole = (string)($plan['finalrole'] ?? '');
            if ($courseid <= 0 || !isset(self::ROLE_RANK[$finalrole])) {
                continue;
            }

            $context = \context_course::instance($courseid);
            $finalroleid = (int)$this->database->get_field(
                'role',
                'id',
                ['shortname' => $finalrole],
                IGNORE_MISSING
            );
            if ($finalroleid <= 0) {
                throw new \coding_exception('Managed learner role does not exist: ' . $finalrole);
            }

            // Keep only the strongest managed learner role on this course.
            foreach (array_keys(self::ROLE_RANK) as $shortname) {
                if ($shortname === $finalrole) {
                    continue;
                }
                $roleid = (int)$this->database->get_field(
                    'role',
                    'id',
                    ['shortname' => $shortname],
                    IGNORE_MISSING
                );
                if ($roleid <= 0) {
                    continue;
                }
                $assignmentcount = $this->database->count_records('role_assignments', [
                    'userid' => $targetuserid,
                    'roleid' => $roleid,
                    'contextid' => $context->id,
                ]);
                if ($assignmentcount > 0) {
                    // Remove every assignment of the lower managed role on this
                    // exact course, independently of component/itemid. Moodle core's
                    // role_unassign_all() treats omitted component/itemid as wildcards
                    // and performs the required dirty-user/event/cache handling.
                    role_unassign_all([
                        'roleid' => $roleid,
                        'userid' => $targetuserid,
                        'contextid' => $context->id,
                    ]);
                    $result['rolesremoved'] += $assignmentcount;
                    if ($shortname === 'trialstudent') {
                        $upgradedfromtrial = true;
                    }
                }
            }

            // Deduplicate the winning role itself if merging produced more than one
            // assignment via different source identities.
            $winning = array_values($this->database->get_records('role_assignments', [
                'userid' => $targetuserid,
                'roleid' => $finalroleid,
                'contextid' => $context->id,
            ], 'id ASC'));
            if ($winning === []) {
                // Match the canonical CampusFR fulfillment behaviour: the learner
                // role belongs to the course context, not to a particular enrol row.
                role_assign($finalroleid, $targetuserid, $context->id);
            } else if (count($winning) > 1) {
                $duplicatecount = count($winning) - 1;
                role_unassign_all([
                    'roleid' => $finalroleid,
                    'userid' => $targetuserid,
                    'contextid' => $context->id,
                ]);
                role_assign($finalroleid, $targetuserid, $context->id);
                $result['rolesdeduplicated'] += $duplicatecount;
            }

            // Normalise only enrolment rows that were snapshotted from one of the
            // two identities for this exact course. We intentionally do not filter by
            // enrol plugin: the existing learning merger already consolidates
            // user_enrolments across plugins/instances, and the role/interval decision
            // must remain correct when Trial and paid access came through different
            // enrolment mechanisms.
            foreach ((array)($plan['enrolids'] ?? []) as $enrolid) {
                $enrolid = (int)$enrolid;
                if ($enrolid <= 0) {
                    continue;
                }
                $instance = $this->database->get_record(
                    'enrol',
                    ['id' => $enrolid, 'courseid' => $courseid],
                    'id,enrol',
                    IGNORE_MISSING
                );
                if (!$instance) {
                    continue;
                }
                $ue = $this->database->get_record('user_enrolments', [
                    'userid' => $targetuserid,
                    'enrolid' => $enrolid,
                ], '*', IGNORE_MISSING);
                if (!$ue) {
                    continue;
                }

                $changed = false;
                foreach ([
                    'timestart' => (int)$plan['timestart'],
                    'timeend' => (int)$plan['timeend'],
                    'status' => (int)$plan['status'],
                ] as $field => $value) {
                    if (property_exists($ue, $field) && (int)$ue->{$field} !== $value) {
                        $ue->{$field} = $value;
                        $changed = true;
                    }
                }
                if ($changed) {
                    if (property_exists($ue, 'timemodified')) {
                        $ue->timemodified = time();
                    }
                    $this->database->update_record('user_enrolments', $ue);
                    $result['enrolmentsnormalised']++;
                }
            }

            $result['courses']++;
        }

        if ($upgradedfromtrial && class_exists(\local_subscriptions\subscription_manager::class)) {
            \local_subscriptions\subscription_manager::cleanup_trial_subscription_if_unused($targetuserid);
        }

        return $result;
    }

    /**
     * @return array<int,array{role:?string,timestart:int,timeend:int,status:int,enrolids:array<int,int>}>
     */
    private function access_by_course(int $userid): array {
        // First build the authoritative course/enrolment interval projection.
        // Do not infer the course from role-assignment SQL: historical CampusFR
        // access can come from different enrol plugins, while roles always live
        // in the Moodle course context.
        $enrolments = [];
        $sql = "SELECT ue.id, ue.enrolid, ue.status, ue.timestart, ue.timeend,
                       e.courseid, e.enrol AS enrolplugin
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid";
        foreach ($this->database->get_records_sql($sql, ['userid' => $userid]) as $row) {
            $courseid = (int)$row->courseid;
            if ($courseid <= 0) {
                continue;
            }
            if (!isset($enrolments[$courseid])) {
                $enrolments[$courseid] = [
                    'timestart' => 0,
                    'timeend' => null,
                    'status' => (int)$row->status,
                    'enrolids' => [],
                ];
            }
            $enrolments[$courseid]['timestart'] = $this->earliest_nonzero(
                (int)$enrolments[$courseid]['timestart'],
                (int)$row->timestart
            );
            $enrolments[$courseid]['timeend'] = $this->union_end(
                $enrolments[$courseid]['timeend'],
                (int)$row->timeend
            );
            $enrolments[$courseid]['status'] = min(
                (int)$enrolments[$courseid]['status'],
                (int)$row->status
            );
            $enrolments[$courseid]['enrolids'][] = (int)$row->enrolid;
        }

        if ($enrolments === []) {
            return [];
        }

        // Resolve managed learner roles by the exact Moodle course context.
        // This avoids the previous blind spot where the role projection could
        // become empty even though role_assignments existed on the course.
        $managedroleids = [];
        foreach ($this->database->get_records_list(
            'role',
            'shortname',
            array_keys(self::ROLE_RANK),
            '',
            'id,shortname'
        ) as $role) {
            $managedroleids[(int)$role->id] = (string)$role->shortname;
        }

        $out = [];
        foreach ($enrolments as $courseid => $access) {
            $context = \context_course::instance((int)$courseid);
            $strongestrole = null;
            $strongestrank = 0;

            foreach ($this->database->get_records('role_assignments', [
                'userid' => $userid,
                'contextid' => $context->id,
            ]) as $assignment) {
                $shortname = $managedroleids[(int)$assignment->roleid] ?? null;
                if ($shortname === null) {
                    continue;
                }
                $rank = self::ROLE_RANK[$shortname] ?? 0;
                if ($rank > $strongestrank) {
                    $strongestrole = $shortname;
                    $strongestrank = $rank;
                }
            }

            $out[(int)$courseid] = [
                'role' => $strongestrole,
                'timestart' => (int)$access['timestart'],
                'timeend' => $access['timeend'] === null ? 0 : (int)$access['timeend'],
                'status' => (int)$access['status'],
                'enrolids' => array_values(array_unique($access['enrolids'])),
            ];
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private function union_access(array $a, array $b): array {
        return [
            'role' => $a['role'],
            'timestart' => $this->earliest_nonzero((int)$a['timestart'], (int)$b['timestart']),
            'timeend' => $this->union_end((int)$a['timeend'], (int)$b['timeend']),
            'status' => min((int)$a['status'], (int)$b['status']),
            'enrolids' => array_values(array_unique(array_merge($a['enrolids'], $b['enrolids']))),
        ];
    }

    private function earliest_nonzero(int $a, int $b): int {
        if ($a <= 0) {
            return max(0, $b);
        }
        if ($b <= 0) {
            return max(0, $a);
        }
        return min($a, $b);
    }

    private function union_end(?int $a, int $b): int {
        if ($a === null) {
            return $b;
        }
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return max($a, $b);
    }
}
