<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Conflict-aware transfer of the pedagogical identity from one Moodle user to another.
 *
 * The service deliberately focuses on state that determines what the learner owns,
 * has completed or can resume. Historical logs are not rewritten: the permanent
 * identity-merge audit keeps the source account reference for forensic purposes.
 */
final class CommerceCustomerLearningMergeService {
    public const BLOCK_PRIVILEGED_ACCOUNT = 'privileged_account';
    public const BLOCK_UNRESOLVED_CONFLICT = 'unresolved_learning_conflict';

    public function __construct(private readonly moodle_database $database) {
    }

    /** @return array<int,array{type:string,userid:int,count:int}> */
    public function blockers(int $sourceuserid, int $targetuserid): array {
        $blockers = [];
        foreach (array_unique([$sourceuserid, $targetuserid]) as $userid) {
            if ($this->is_privileged($userid)) {
                $blockers[] = [
                    'type' => self::BLOCK_PRIVILEGED_ACCOUNT,
                    'userid' => $userid,
                    'count' => 1,
                ];
            }
        }
        return $blockers;
    }

    /**
     * Return pedagogical conflicts that deserve an explicit human decision.
     * Safe unions (attempt history, groups, badges, enrolments...) are deliberately absent.
     *
     * @return array<int,array<string,mixed>>
     */
    public function conflicts(int $sourceuserid, int $targetuserid): array {
        $conflicts = [];

        if ($this->table_has_field('course_modules_completion', 'userid')) {
            foreach ($this->database->get_records('course_modules_completion', ['userid' => $sourceuserid]) as $source) {
                $target = $this->database->get_record('course_modules_completion', [
                    'userid' => $targetuserid,
                    'coursemoduleid' => $source->coursemoduleid,
                ]);
                if (!$target || (int)$source->completionstate === (int)$target->completionstate) {
                    continue;
                }
                $conflicts[] = $this->conflict(
                    'activity_completion',
                    $sourceuserid,
                    $targetuserid,
                    (int)$source->coursemoduleid,
                    (string)(int)$source->completionstate,
                    (string)(int)$target->completionstate,
                    (int)$source->completionstate > (int)$target->completionstate ? 'source' : 'target'
                );
            }
        }

        if ($this->table_has_field('grade_grades', 'userid')) {
            foreach ($this->database->get_records('grade_grades', ['userid' => $sourceuserid]) as $source) {
                $target = $this->database->get_record('grade_grades', [
                    'userid' => $targetuserid,
                    'itemid' => $source->itemid,
                ]);
                if (!$target || $source->finalgrade === null || $target->finalgrade === null
                        || (float)$source->finalgrade === (float)$target->finalgrade) {
                    continue;
                }
                $conflicts[] = $this->conflict(
                    'grade',
                    $sourceuserid,
                    $targetuserid,
                    (int)$source->itemid,
                    (string)$source->finalgrade,
                    (string)$target->finalgrade,
                    (float)$source->finalgrade > (float)$target->finalgrade ? 'source' : 'target'
                );
            }
        }
        return $conflicts;
    }

    /** @return array<string,mixed> */
    private function conflict(string $type, int $sourceuserid, int $targetuserid, int $itemid,
            string $sourcevalue, string $targetvalue, string $recommended): array {
        $key = $type . ':' . $sourceuserid . ':' . $targetuserid . ':' . $itemid;
        return [
            'id' => substr(hash('sha256', $key), 0, 20),
            'type' => $type,
            'itemid' => $itemid,
            'sourceuserid' => $sourceuserid,
            'targetuserid' => $targetuserid,
            'sourcevalue' => $sourcevalue,
            'targetvalue' => $targetvalue,
            'recommended' => $recommended,
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function conflict_map(int $sourceuserid, int $targetuserid): array {
        $out = [];
        foreach ($this->conflicts($sourceuserid, $targetuserid) as $conflict) {
            $out[$conflict['id']] = $conflict;
        }
        return $out;
    }

    /** @return array<string,int> */
    public function preview(int $sourceuserid): array {
        $tables = [
            'user_enrolments' => 'enrolments',
            'course_completions' => 'coursecompletions',
            'course_completion_crit_compl' => 'completioncriteria',
            'course_modules_completion' => 'activitycompletions',
            'grade_grades' => 'grades',
            'quiz_attempts' => 'quizattempts',
            'h5pactivity_attempts' => 'h5pattempts',
            'assign_submission' => 'assignsubmissions',
            'groups_members' => 'groupmemberships',
            'badges_issued' => 'badges',
        ];
        $out = [];
        foreach ($tables as $table => $key) {
            $out[$key] = $this->count_user_records($table, 'userid', $sourceuserid);
        }
        return $out;
    }

    /**
     * Merge the supported pedagogical state.
     *
     * @return array<string,int>
     */
    public function merge(int $sourceuserid, int $targetuserid, array $resolutions = []): array {
        $result = [
            'enrolments' => 0,
            'enrolmentsdeduplicated' => 0,
            'roleassignments' => 0,
            'roleassignmentsdeduplicated' => 0,
            'groupmemberships' => 0,
            'groupmembershipsdeduplicated' => 0,
            'coursecompletions' => 0,
            'coursecompletionsmerged' => 0,
            'completioncriteria' => 0,
            'completioncriteriamerged' => 0,
            'activitycompletions' => 0,
            'activitycompletionsmerged' => 0,
            'grades' => 0,
            'gradesmerged' => 0,
            'gradehistory' => 0,
            'quizattempts' => 0,
            'quizgrades' => 0,
            'h5pattempts' => 0,
            'assignsubmissions' => 0,
            'assigngrades' => 0,
            'questionsteps' => 0,
            'lastaccess' => 0,
            'badges' => 0,
            'badgesdeduplicated' => 0,
            'competencies' => 0,
            'forumposts' => 0,
            'forumdiscussions' => 0,
            'scormtracks' => 0,
            'lessonrecords' => 0,
        ];

        [$result['enrolments'], $result['enrolmentsdeduplicated']] =
            $this->merge_enrolments($sourceuserid, $targetuserid);
        [$result['roleassignments'], $result['roleassignmentsdeduplicated']] =
            $this->merge_role_assignments($sourceuserid, $targetuserid);
        [$result['groupmemberships'], $result['groupmembershipsdeduplicated']] =
            $this->merge_unique_membership('groups_members', 'groupid', $sourceuserid, $targetuserid);
        [$result['coursecompletions'], $result['coursecompletionsmerged']] =
            $this->merge_course_completions($sourceuserid, $targetuserid);
        [$result['completioncriteria'], $result['completioncriteriamerged']] =
            $this->merge_completion_criteria($sourceuserid, $targetuserid);
        $conflictmap = $this->conflict_map($sourceuserid, $targetuserid);
        [$result['activitycompletions'], $result['activitycompletionsmerged']] =
            $this->merge_activity_completions($sourceuserid, $targetuserid, $resolutions, $conflictmap);
        [$result['grades'], $result['gradesmerged']] =
            $this->merge_grades($sourceuserid, $targetuserid, $resolutions, $conflictmap);

        $result['gradehistory'] = $this->move_if_exists('grade_grades_history', 'userid', $sourceuserid, $targetuserid);
        $result['quizattempts'] = $this->merge_numbered_attempts('quiz_attempts', 'quiz', 'attempt', $sourceuserid, $targetuserid);
        $result['quizgrades'] = $this->merge_best_value('quiz_grades', 'quiz', 'grade', $sourceuserid, $targetuserid);
        $result['h5pattempts'] = $this->merge_numbered_attempts('h5pactivity_attempts', 'h5pactivityid', 'attempt', $sourceuserid, $targetuserid);

        [$result['assignsubmissions'], $result['assigngrades']] = $this->merge_assignments($sourceuserid, $targetuserid);
        $result['questionsteps'] = $this->move_if_exists('question_attempt_steps', 'userid', $sourceuserid, $targetuserid);
        $result['lastaccess'] = $this->merge_last_access($sourceuserid, $targetuserid);
        [$result['badges'], $result['badgesdeduplicated']] = $this->merge_badges($sourceuserid, $targetuserid);
        $result['competencies'] = $this->merge_competencies($sourceuserid, $targetuserid);

        // Authored learning content follows the identity. Audit/event logs intentionally do not.
        $result['forumposts'] = $this->move_if_exists('forum_posts', 'userid', $sourceuserid, $targetuserid);
        $result['forumdiscussions'] = $this->move_if_exists('forum_discussions', 'userid', $sourceuserid, $targetuserid);
        $result['scormtracks'] = $this->merge_scorm_tracks($sourceuserid, $targetuserid);
        foreach (['lesson_attempts', 'lesson_grades', 'lesson_timer'] as $table) {
            $result['lessonrecords'] += $this->move_if_exists($table, 'userid', $sourceuserid, $targetuserid);
        }

        return $result;
    }

    private function is_privileged(int $userid): bool {
        global $CFG;
        if (function_exists('is_siteadmin') && is_siteadmin($userid)) {
            return true;
        }
        if (!$this->table_has_field('role_assignments', 'userid')) {
            return false;
        }
        $systemcontext = \context_system::instance();
        return $this->database->record_exists('role_assignments', [
            'userid' => $userid,
            'contextid' => $systemcontext->id,
        ]);
    }

    /** @return array{0:int,1:int} */
    private function merge_enrolments(int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field('user_enrolments', 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $deduped = 0;
        foreach ($this->database->get_records('user_enrolments', ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record('user_enrolments', [
                'userid' => $targetuserid,
                'enrolid' => $source->enrolid,
            ]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record('user_enrolments', $source);
                $moved++;
                continue;
            }
            foreach (['timestart', 'timecreated'] as $field) {
                if (property_exists($target, $field) && property_exists($source, $field)) {
                    $target->{$field} = $this->earliest_nonzero((int)$target->{$field}, (int)$source->{$field});
                }
            }
            if (property_exists($target, 'timeend') && property_exists($source, 'timeend')) {
                $target->timeend = ((int)$target->timeend === 0 || (int)$source->timeend === 0)
                    ? 0 : max((int)$target->timeend, (int)$source->timeend);
            }
            if (property_exists($target, 'status') && property_exists($source, 'status')) {
                $target->status = min((int)$target->status, (int)$source->status);
            }
            if (property_exists($target, 'timemodified') && property_exists($source, 'timemodified')) {
                $target->timemodified = max((int)$target->timemodified, (int)$source->timemodified);
            }
            $this->database->update_record('user_enrolments', $target);
            $this->database->delete_records('user_enrolments', ['id' => $source->id]);
            $deduped++;
        }
        return [$moved, $deduped];
    }

    /** @return array{0:int,1:int} */
    private function merge_role_assignments(int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field('role_assignments', 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $deduped = 0;
        foreach ($this->database->get_records('role_assignments', ['userid' => $sourceuserid]) as $source) {
            $identity = [
                'userid' => $targetuserid,
                'roleid' => (int)$source->roleid,
                'contextid' => (int)$source->contextid,
                'component' => (string)$source->component,
                'itemid' => (int)$source->itemid,
            ];
            if ($this->database->record_exists('role_assignments', $identity)) {
                $this->database->delete_records('role_assignments', ['id' => $source->id]);
                $deduped++;
            } else {
                $source->userid = $targetuserid;
                $this->database->update_record('role_assignments', $source);
                $moved++;
            }
        }
        return [$moved, $deduped];
    }

    /** @return array{0:int,1:int} */
    private function merge_unique_membership(string $table, string $keyfield, int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field($table, 'userid') || !$this->table_has_field($table, $keyfield)) {
            return [0, 0];
        }
        $moved = 0;
        $deduped = 0;
        foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $row) {
            if ($this->database->record_exists($table, ['userid' => $targetuserid, $keyfield => $row->{$keyfield}])) {
                $this->database->delete_records($table, ['id' => $row->id]);
                $deduped++;
            } else {
                $row->userid = $targetuserid;
                $this->database->update_record($table, $row);
                $moved++;
            }
        }
        return [$moved, $deduped];
    }

    /** @return array{0:int,1:int} */
    private function merge_course_completions(int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field('course_completions', 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $merged = 0;
        foreach ($this->database->get_records('course_completions', ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record('course_completions', ['userid' => $targetuserid, 'course' => $source->course]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record('course_completions', $source);
                $moved++;
                continue;
            }
            foreach (['timeenrolled', 'timestarted'] as $field) {
                if (property_exists($target, $field) && property_exists($source, $field)) {
                    $target->{$field} = $this->earliest_nonzero((int)$target->{$field}, (int)$source->{$field});
                }
            }
            if (property_exists($target, 'timecompleted') && property_exists($source, 'timecompleted')) {
                $target->timecompleted = $this->earliest_nonzero((int)$target->timecompleted, (int)$source->timecompleted);
            }
            if (property_exists($target, 'reaggregate') && property_exists($source, 'reaggregate')) {
                $target->reaggregate = $this->earliest_nonzero((int)$target->reaggregate, (int)$source->reaggregate);
            }
            $this->database->update_record('course_completions', $target);
            $this->database->delete_records('course_completions', ['id' => $source->id]);
            $merged++;
        }
        return [$moved, $merged];
    }

    /** @return array{0:int,1:int} */
    private function merge_completion_criteria(int $sourceuserid, int $targetuserid): array {
        $table = 'course_completion_crit_compl';
        if (!$this->table_has_field($table, 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $merged = 0;
        foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $source) {
            $conditions = ['userid' => $targetuserid];
            foreach (['course', 'criteriaid'] as $field) {
                if (property_exists($source, $field)) {
                    $conditions[$field] = $source->{$field};
                }
            }
            $target = $this->database->get_record($table, $conditions);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record($table, $source);
                $moved++;
            } else {
                if (property_exists($target, 'timecompleted') && property_exists($source, 'timecompleted')) {
                    $target->timecompleted = $this->earliest_nonzero((int)$target->timecompleted, (int)$source->timecompleted);
                    $this->database->update_record($table, $target);
                }
                $this->database->delete_records($table, ['id' => $source->id]);
                $merged++;
            }
        }
        return [$moved, $merged];
    }

    /** @return array{0:int,1:int} */
    private function merge_activity_completions(int $sourceuserid, int $targetuserid, array $resolutions, array $conflictmap): array {
        $table = 'course_modules_completion';
        if (!$this->table_has_field($table, 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $merged = 0;
        foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record($table, ['userid' => $targetuserid, 'coursemoduleid' => $source->coursemoduleid]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record($table, $source);
                $moved++;
                continue;
            }
            if (property_exists($target, 'completionstate')) {
                $sourcevalue = (int)($source->completionstate ?? 0);
                $choice = $this->resolution_for('activity_completion', (int)$source->coursemoduleid, $conflictmap, $resolutions);
                if ($choice === 'source' || ($choice === null && $sourcevalue > (int)$target->completionstate)) {
                    $target->completionstate = $sourcevalue;
                    foreach (['overrideby', 'valueused'] as $field) {
                        if (property_exists($source, $field) && property_exists($target, $field)) {
                            $target->{$field} = $source->{$field};
                        }
                    }
                }
            }
            if (property_exists($target, 'viewed') && property_exists($source, 'viewed')) {
                $target->viewed = max((int)$target->viewed, (int)$source->viewed);
            }
            if (property_exists($target, 'timemodified') && property_exists($source, 'timemodified')) {
                $target->timemodified = max((int)$target->timemodified, (int)$source->timemodified);
            }
            $this->database->update_record($table, $target);
            $this->database->delete_records($table, ['id' => $source->id]);
            $merged++;
        }
        return [$moved, $merged];
    }

    /** @return array{0:int,1:int} */
    private function merge_grades(int $sourceuserid, int $targetuserid, array $resolutions, array $conflictmap): array {
        if (!$this->table_has_field('grade_grades', 'userid')) {
            return [0, 0];
        }
        $moved = 0;
        $merged = 0;
        foreach ($this->database->get_records('grade_grades', ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record('grade_grades', ['userid' => $targetuserid, 'itemid' => $source->itemid]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record('grade_grades', $source);
                $moved++;
                continue;
            }
            $choice = $this->resolution_for('grade', (int)$source->itemid, $conflictmap, $resolutions);
            if ($choice === 'source' || ($choice === null && $this->grade_value($source) > $this->grade_value($target))) {
                foreach (get_object_vars($source) as $field => $value) {
                    if (in_array($field, ['id', 'itemid', 'userid'], true) || !property_exists($target, $field)) {
                        continue;
                    }
                    $target->{$field} = $value;
                }
                $target->userid = $targetuserid;
                $this->database->update_record('grade_grades', $target);
            }
            $this->database->delete_records('grade_grades', ['id' => $source->id]);
            $merged++;
        }
        return [$moved, $merged];
    }

    private function resolution_for(string $type, int $itemid, array $conflictmap, array $resolutions): ?string {
        foreach ($conflictmap as $id => $conflict) {
            if ($conflict['type'] !== $type || (int)$conflict['itemid'] !== $itemid) {
                continue;
            }
            $choice = $resolutions[$id] ?? null;
            return in_array($choice, ['source', 'target'], true) ? $choice : null;
        }
        return null;
    }

    private function grade_value(object $grade): float {
        return $grade->finalgrade === null ? -INF : (float)$grade->finalgrade;
    }

    private function merge_numbered_attempts(string $table, string $activityfield, string $attemptfield, int $sourceuserid, int $targetuserid): int {
        if (!$this->table_has_field($table, 'userid') || !$this->table_has_field($table, $activityfield) || !$this->table_has_field($table, $attemptfield)) {
            return 0;
        }
        $rows = $this->database->get_records($table, ['userid' => $sourceuserid], $activityfield . ' ASC, ' . $attemptfield . ' ASC, id ASC');
        $next = [];
        $count = 0;
        foreach ($rows as $row) {
            $activityid = (int)$row->{$activityfield};
            if (!isset($next[$activityid])) {
                $max = $this->database->get_field_sql(
                    'SELECT MAX(' . $attemptfield . ') FROM {' . $table . '} WHERE userid = :userid AND ' . $activityfield . ' = :activityid',
                    ['userid' => $targetuserid, 'activityid' => $activityid]
                );
                $next[$activityid] = ((int)$max) + 1;
            }
            $row->userid = $targetuserid;
            $row->{$attemptfield} = $next[$activityid]++;
            $this->database->update_record($table, $row);
            $count++;
        }
        return $count;
    }

    private function merge_best_value(string $table, string $keyfield, string $valuefield, int $sourceuserid, int $targetuserid): int {
        if (!$this->table_has_field($table, 'userid')) {
            return 0;
        }
        $count = 0;
        foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record($table, ['userid' => $targetuserid, $keyfield => $source->{$keyfield}]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record($table, $source);
            } else {
                if ((float)$source->{$valuefield} > (float)$target->{$valuefield}) {
                    $target->{$valuefield} = $source->{$valuefield};
                    if (property_exists($target, 'timemodified') && property_exists($source, 'timemodified')) {
                        $target->timemodified = max((int)$target->timemodified, (int)$source->timemodified);
                    }
                    $this->database->update_record($table, $target);
                }
                $this->database->delete_records($table, ['id' => $source->id]);
            }
            $count++;
        }
        return $count;
    }

    /** @return array{0:int,1:int} */
    private function merge_assignments(int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field('assign_submission', 'userid')) {
            return [0, 0];
        }
        $submissions = 0;
        $grades = 0;
        $next = [];
        foreach ($this->database->get_records('assign_submission', ['userid' => $sourceuserid], 'assignment ASC, attemptnumber ASC, id ASC') as $submission) {
            $assignmentid = (int)$submission->assignment;
            if (!isset($next[$assignmentid])) {
                $max = $this->database->get_field_sql(
                    'SELECT MAX(attemptnumber) FROM {assign_submission} WHERE userid = :userid AND assignment = :assignment',
                    ['userid' => $targetuserid, 'assignment' => $assignmentid]
                );
                $next[$assignmentid] = ((int)$max) + 1;
            }
            $oldattempt = (int)$submission->attemptnumber;
            $newattempt = $next[$assignmentid]++;
            $submission->userid = $targetuserid;
            $submission->attemptnumber = $newattempt;
            $this->database->update_record('assign_submission', $submission);
            $submissions++;

            if ($this->table_has_field('assign_grades', 'userid')) {
                foreach ($this->database->get_records('assign_grades', [
                    'userid' => $sourceuserid,
                    'assignment' => $assignmentid,
                    'attemptnumber' => $oldattempt,
                ]) as $grade) {
                    $grade->userid = $targetuserid;
                    $grade->attemptnumber = $newattempt;
                    $this->database->update_record('assign_grades', $grade);
                    $grades++;
                }
            }
        }
        // Defensive fallback for grades without a matching submission record.
        $grades += $this->move_if_exists('assign_grades', 'userid', $sourceuserid, $targetuserid);
        return [$submissions, $grades];
    }

    private function merge_last_access(int $sourceuserid, int $targetuserid): int {
        $table = 'user_lastaccess';
        if (!$this->table_has_field($table, 'userid')) {
            return 0;
        }
        $count = 0;
        foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $source) {
            $target = $this->database->get_record($table, ['userid' => $targetuserid, 'courseid' => $source->courseid]);
            if (!$target) {
                $source->userid = $targetuserid;
                $this->database->update_record($table, $source);
            } else {
                $target->timeaccess = max((int)$target->timeaccess, (int)$source->timeaccess);
                $this->database->update_record($table, $target);
                $this->database->delete_records($table, ['id' => $source->id]);
            }
            $count++;
        }
        return $count;
    }

    /** @return array{0:int,1:int} */
    private function merge_badges(int $sourceuserid, int $targetuserid): array {
        if (!$this->table_has_field('badge_issued', 'userid') && !$this->table_has_field('badges_issued', 'userid')) {
            return [0, 0];
        }
        $table = $this->table_has_field('badge_issued', 'userid') ? 'badge_issued' : 'badges_issued';
        $key = $this->table_has_field($table, 'badgeid') ? 'badgeid' : 'badgeid';
        return $this->merge_unique_membership($table, $key, $sourceuserid, $targetuserid);
    }

    private function merge_competencies(int $sourceuserid, int $targetuserid): int {
        $count = 0;
        foreach ([
            ['competency_usercomp', ['competencyid']],
            ['competency_usercompcourse', ['competencyid', 'courseid']],
        ] as [$table, $keys]) {
            if (!$this->table_has_field($table, 'userid')) {
                continue;
            }
            foreach ($this->database->get_records($table, ['userid' => $sourceuserid]) as $source) {
                $conditions = ['userid' => $targetuserid];
                foreach ($keys as $key) {
                    $conditions[$key] = $source->{$key};
                }
                $target = $this->database->get_record($table, $conditions);
                if (!$target) {
                    $source->userid = $targetuserid;
                    $this->database->update_record($table, $source);
                } else {
                    if (property_exists($target, 'proficiency') && !empty($source->proficiency)) {
                        $target->proficiency = 1;
                    }
                    if (property_exists($target, 'grade') && (float)$source->grade > (float)$target->grade) {
                        $target->grade = $source->grade;
                    }
                    $this->database->update_record($table, $target);
                    $this->database->delete_records($table, ['id' => $source->id]);
                }
                $count++;
            }
        }
        $count += $this->move_if_exists('competency_plan', 'userid', $sourceuserid, $targetuserid);
        return $count;
    }

    private function merge_scorm_tracks(int $sourceuserid, int $targetuserid): int {
        $table = 'scorm_scoes_track';
        if (!$this->table_has_field($table, 'userid')) {
            return 0;
        }
        $rows = $this->database->get_records($table, ['userid' => $sourceuserid], 'scormid ASC, attempt ASC, id ASC');
        $offsets = [];
        $count = 0;
        foreach ($rows as $row) {
            $scormid = (int)$row->scormid;
            if (!isset($offsets[$scormid])) {
                $max = $this->database->get_field_sql(
                    'SELECT MAX(attempt) FROM {scorm_scoes_track} WHERE userid = :userid AND scormid = :scormid',
                    ['userid' => $targetuserid, 'scormid' => $scormid]
                );
                $offsets[$scormid] = (int)$max;
            }
            $row->userid = $targetuserid;
            $row->attempt = (int)$row->attempt + $offsets[$scormid];
            $this->database->update_record($table, $row);
            $count++;
        }
        return $count;
    }

    private function move_if_exists(string $table, string $field, int $sourceuserid, int $targetuserid): int {
        if (!$this->table_has_field($table, $field)) {
            return 0;
        }
        $count = (int)$this->database->count_records($table, [$field => $sourceuserid]);
        if ($count > 0) {
            $this->database->set_field($table, $field, $targetuserid, [$field => $sourceuserid]);
        }
        return $count;
    }

    private function count_user_records(string $table, string $field, int $userid): int {
        return $this->table_has_field($table, $field)
            ? (int)$this->database->count_records($table, [$field => $userid])
            : 0;
    }

    private function table_has_field(string $table, string $field): bool {
        $manager = $this->database->get_manager();
        $xmldbtable = new \xmldb_table($table);
        return $manager->table_exists($xmldbtable)
            && $manager->field_exists($xmldbtable, new \xmldb_field($field));
    }

    private function earliest_nonzero(int $a, int $b): int {
        if ($a <= 0) {
            return max(0, $b);
        }
        if ($b <= 0) {
            return $a;
        }
        return min($a, $b);
    }
}
