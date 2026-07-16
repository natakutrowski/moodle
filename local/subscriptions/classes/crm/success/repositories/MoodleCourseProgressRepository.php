<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads native Moodle course completion data.
 */
final class MoodleCourseProgressRepository {

    /**
     * Returns active enrolled courses for a user.
     *
     * @return \stdClass[]
     */
    public function get_accessible_courses(
        int $userid
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Course progress userid must be greater than zero.'
            );
        }

        $courses = enrol_get_users_courses(
            $userid,
            true,
            'id,fullname,shortname,visible,startdate,enddate'
        );

        return array_values(
            array_filter(
                $courses,
                static fn(\stdClass $course): bool =>
                    (int)$course->id > SITEID
            )
        );
    }

    /**
     * Returns grouped progress data for all supplied courses.
     *
     * @param int[] $courseids
     * @return array<int,\stdClass>
     */
    public function get_course_progress_records(
        int $userid,
        array $courseids
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Course progress userid must be greater than zero.'
            );
        }

        $courseids = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $courseids),
                    static fn(int $courseid): bool =>
                        $courseid > SITEID
                )
            )
        );

        if ($courseids === []) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            'successcourse'
        );

        $params = [
            'userid' => $userid,
            'completiondisabled' => 0,
            'completionincomplete' => 0,
        ] + $inparams;

        $sql = "
            SELECT
                cm.course AS courseid,
                COUNT(cm.id) AS trackedactivities,
                SUM(
                    CASE
                        WHEN cmc.completionstate > :completionincomplete
                        THEN 1
                        ELSE 0
                    END
                ) AS completedactivities,
                MAX(cmc.timemodified) AS lastcompletionat
              FROM {course_modules} cm
         LEFT JOIN {course_modules_completion} cmc
                ON cmc.coursemoduleid = cm.id
               AND cmc.userid = :userid
             WHERE cm.course {$insql}
               AND cm.deletioninprogress = 0
               AND cm.completion <> :completiondisabled
          GROUP BY cm.course
        ";

        $records = $DB->get_records_sql(
            $sql,
            $params
        );

        $completionrecords =
            $this->get_course_completion_records(
                $userid,
                $courseids
            );

        $result = [];

        foreach ($courseids as $courseid) {
            $record =
                $records[$courseid] ?? null;

            $tracked = $record !== null
                ? (int)$record->trackedactivities
                : 0;

            $completed = $record !== null
                ? (int)$record->completedactivities
                : 0;

            $completion =
                $completionrecords[$courseid] ?? null;

            $progress = $tracked > 0
                ? round(
                    ($completed / $tracked) * 100,
                    2
                )
                : null;

            $result[$courseid] = (object)[
                'courseid' => $courseid,
                'trackedactivities' => $tracked,
                'completedactivities' => $completed,
                'remainingactivities' => max(
                    0,
                    $tracked - $completed
                ),
                'progresspercentage' => $progress,
                'lastcompletionat' => $record !== null
                    ? (int)$record->lastcompletionat
                    : 0,
                'coursecompleted' =>
                    $completion !== null &&
                    (int)$completion->timecompleted > 0,
                'coursecompletedat' =>
                    $completion !== null
                        ? (int)$completion->timecompleted
                        : 0,
            ];
        }

        return $result;
    }

    /**
     * @param int[] $courseids
     * @return array<int,\stdClass>
     */
    private function get_course_completion_records(
        int $userid,
        array $courseids
    ): array {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            'completedcourse'
        );

        $records = $DB->get_records_select(
            'course_completions',
            "userid = :userid AND course {$insql}",
            ['userid' => $userid] + $inparams,
            '',
            'id,course,timecompleted,timestarted,timeenrolled'
        );

        $bycourse = [];

        foreach ($records as $record) {
            $bycourse[(int)$record->course] = $record;
        }

        return $bycourse;
    }
}