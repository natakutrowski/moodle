<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Builds a non-destructive merge plan for several Moodle accounts.
 *
 * Pedagogical history is deliberately the primary recommendation criterion.
 * Commerce richness and account quality are only tie-breakers.
 */
final class CommerceCustomerMergePlanner {
    public const MIN_ACCOUNTS = 2;
    public const MAX_ACCOUNTS = 10;

    public const WARNING_PEDAGOGICAL_HISTORY = 'pedagogical_history';
    public const WARNING_SHARED_COURSES = 'shared_courses';
    public const WARNING_DIFFERENT_EMAILS = 'different_emails';
    public const WARNING_SUSPENDED_TARGET = 'suspended_target';

    public function __construct(
        private readonly moodle_database $database
    ) {
    }

    public function build(
        array $userids,
        ?int $targetuserid = null
    ): CommerceCustomerMergePlan {
        $userids = array_values(array_unique(array_filter(
            array_map('intval', $userids),
            static fn(int $userid): bool => $userid > 1
        )));

        if (
            count($userids) < self::MIN_ACCOUNTS
            || count($userids) > self::MAX_ACCOUNTS
        ) {
            throw new \invalid_parameter_exception(
                'A merge plan requires between 2 and 10 Moodle accounts.'
            );
        }

        $profiles = [];
        foreach ($userids as $userid) {
            $profiles[] = $this->profile($userid);
        }

        usort($profiles, [$this, 'compare_profiles']);
        $recommended = $profiles[0]->userid();

        if ($targetuserid === null) {
            $targetuserid = $recommended;
        }
        if (!in_array($targetuserid, $userids, true)) {
            throw new \invalid_parameter_exception(
                'The selected merge target is not one of the compared accounts.'
            );
        }

        $warnings = [];
        $emails = [];

        foreach ($profiles as $profile) {
            $email = \core_text::strtolower(trim((string)$profile->user->email));
            if ($email !== '') {
                $emails[$email] = true;
            }

            if (
                $profile->userid() !== $targetuserid
                && $profile->has_pedagogical_history()
            ) {
                $warnings[] = [
                    'type' => self::WARNING_PEDAGOGICAL_HISTORY,
                    'userid' => $profile->userid(),
                    'detail' => 'source_has_pedagogical_history',
                ];
            }
        }

        if (count($emails) > 1) {
            $warnings[] = [
                'type' => self::WARNING_DIFFERENT_EMAILS,
                'userid' => 0,
                'detail' => 'accounts_use_different_emails',
            ];
        }

        $sharedcoursecount = $this->shared_course_count($userids);
        if ($sharedcoursecount > 0) {
            $warnings[] = [
                'type' => self::WARNING_SHARED_COURSES,
                'userid' => 0,
                'detail' => 'pedagogical_histories_overlap',
            ];
        }

        $targetprofile = null;
        foreach ($profiles as $profile) {
            if ($profile->userid() === $targetuserid) {
                $targetprofile = $profile;
                break;
            }
        }
        if (
            $targetprofile !== null
            && (int)$targetprofile->user->suspended === 1
        ) {
            $warnings[] = [
                'type' => self::WARNING_SUSPENDED_TARGET,
                'userid' => $targetuserid,
                'detail' => 'target_is_suspended',
            ];
        }

        return new CommerceCustomerMergePlan(
            $profiles,
            $recommended,
            $targetuserid,
            $warnings,
            $sharedcoursecount
        );
    }

    private function profile(int $userid): CommerceCustomerMergeAccountProfile {
        global $CFG;

        $user = $this->database->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            'id,username,firstname,lastname,email,confirmed,suspended,timecreated,lastaccess',
            MUST_EXIST
        );

        $enrolledcourses = (int)$this->database->count_records_sql(
            'SELECT COUNT(DISTINCT e.courseid)
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid',
            ['userid' => $userid]
        );

        $completedcourses = (int)$this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {course_completions}
              WHERE userid = :userid
                AND timecompleted IS NOT NULL
                AND timecompleted > 0',
            ['userid' => $userid]
        );

        $completedactivities = (int)$this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {course_modules_completion}
              WHERE userid = :userid
                AND completionstate <> 0',
            ['userid' => $userid]
        );

        [$gradecount, $averagegradepercent] = $this->grade_summary($userid);

        return new CommerceCustomerMergeAccountProfile(
            $user,
            $enrolledcourses,
            $completedcourses,
            $completedactivities,
            $gradecount,
            $averagegradepercent,
            (int)$this->database->count_records(
                'local_subscriptions_commerce_purchase',
                ['userid' => $userid]
            ),
            (int)$this->database->count_records(
                'local_subs_commerce_grant',
                ['beneficiaryuserid' => $userid]
            ),
            (int)$this->database->count_records(
                'local_subs_commerce_dig_access',
                ['beneficiaryuserid' => $userid]
            ),
            (int)$this->database->count_records(
                'local_subs_commerce_guest',
                ['userid' => $userid]
            )
        );
    }

    /**
     * Pedagogy first, then Commerce richness, then account quality.
     */
    private function compare_profiles(
        CommerceCustomerMergeAccountProfile $a,
        CommerceCustomerMergeAccountProfile $b
    ): int {
        foreach ([
            [$b->pedagogical_score(), $a->pedagogical_score()],
            [$b->completedcourses, $a->completedcourses],
            [$b->completedactivities, $a->completedactivities],
            [$b->enrolledcourses, $a->enrolledcourses],
            [$b->averagegradepercent, $a->averagegradepercent],
            [$b->commerce_score(), $a->commerce_score()],
            [(int)!$b->user->suspended, (int)!$a->user->suspended],
            [(int)$b->user->confirmed, (int)$a->user->confirmed],
            [(int)$b->user->lastaccess, (int)$a->user->lastaccess],
        ] as [$left, $right]) {
            if ($left != $right) {
                return $left <=> $right;
            }
        }

        return (int)$a->user->id <=> (int)$b->user->id;
    }

    /**
     * @return array{0:int,1:float}
     */
    private function grade_summary(int $userid): array {
        $records = $this->database->get_records_sql(
            'SELECT gg.id, gg.finalgrade, gi.grademin, gi.grademax
               FROM {grade_grades} gg
               JOIN {grade_items} gi ON gi.id = gg.itemid
              WHERE gg.userid = :userid
                AND gg.finalgrade IS NOT NULL',
            ['userid' => $userid]
        );

        $percentages = [];
        foreach ($records as $record) {
            $min = (float)$record->grademin;
            $max = (float)$record->grademax;
            if ($max <= $min) {
                continue;
            }

            $percentages[] = max(
                0.0,
                min(
                    100.0,
                    100.0 * (((float)$record->finalgrade - $min) / ($max - $min))
                )
            );
        }

        return [
            count($records),
            $percentages === []
                ? 0.0
                : array_sum($percentages) / count($percentages),
        ];
    }

    private function shared_course_count(array $userids): int {
        [$insql, $params] = $this->database->get_in_or_equal(
            $userids,
            SQL_PARAMS_NAMED,
            'mergeuid'
        );

        $records = $this->database->get_records_sql(
            'SELECT e.courseid, COUNT(DISTINCT ue.userid) AS usercount
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid ' . $insql . '
           GROUP BY e.courseid
             HAVING COUNT(DISTINCT ue.userid) > 1',
            $params
        );

        return count($records);
    }
}
