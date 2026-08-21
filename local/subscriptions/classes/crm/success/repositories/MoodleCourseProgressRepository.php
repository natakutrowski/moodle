<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\runtime\CustomerSuccessRepositoryProfiler;

/**
 * Reads native Moodle course completion data.
 */
final class MoodleCourseProgressRepository {

    public function __construct(
        private readonly EnrolledCourseProvider $courseprovider =
            new EnrolledCourseProvider()
    ) {
    }

    /**
     * Returns active enrolled courses for a user.
     *
     * @return \stdClass[]
     */
    public function get_accessible_courses(
        int $userid
    ): array {
        return CustomerSuccessRepositoryProfiler::measure(
            'moodle_course_progress',
            $userid,
            'accessible_courses',
            fn(): array =>
                $this->courseprovider
                    ->get_courses($userid)
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
            'useridcompletion' => $userid,
            'useridcourse' => $userid,
            'completiondisabled' => 0,
            'completionincomplete' => 0,
        ] + $inparams;

        $sql = "
            SELECT
                course.id AS courseid,

                COUNT(cm.id) AS trackedactivities,

                COALESCE(
                    SUM(
                        CASE
                            WHEN cmc.completionstate >
                                :completionincomplete
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS completedactivities,

                MAX(cmc.timemodified) AS lastcompletionat,

                MAX(completion.timecompleted) AS coursecompletedat

              FROM {course} course

         LEFT JOIN {course_modules} cm
                ON cm.course = course.id
               AND cm.deletioninprogress = 0
               AND cm.completion <>
                    :completiondisabled

         LEFT JOIN {course_modules_completion} cmc
                ON cmc.coursemoduleid = cm.id
               AND cmc.userid = :useridcompletion

         LEFT JOIN {course_completions} completion
                ON completion.course = course.id
               AND completion.userid = :useridcourse

             WHERE course.id {$insql}

          GROUP BY course.id
        ";

        $records =
            CustomerSuccessRepositoryProfiler::measure(
                'moodle_course_progress',
                $userid,
                'completion_query',
                fn(): array =>
                    $DB->get_records_sql(
                        $sql,
                        $params
                    )
            );

        $latestactivity = CustomerSuccessRepositoryProfiler::measure(
            'moodle_course_progress',
            $userid,
            'latest_completed_activity',
            function () use (
                $DB,
                $userid,
                $insql,
                $inparams
            ): array {
                $sql = "
                    SELECT
                        cm.course AS courseid,
                        cm.id AS coursemoduleid,
                        cmc.timemodified
                      FROM {course_modules_completion} cmc
                      JOIN {course_modules} cm
                        ON cm.id = cmc.coursemoduleid
                       AND cm.deletioninprogress = 0
                     WHERE cmc.userid = :latestuserid
                       AND cmc.completionstate > :latestincomplete
                       AND cm.course {$insql}
                  ORDER BY cm.course ASC,
                           cmc.timemodified DESC,
                           cm.id DESC
                ";

                $rows = $DB->get_records_sql(
                    $sql,
                    [
                        'latestuserid' => $userid,
                        'latestincomplete' => 0,
                    ] + $inparams
                );

                $result = [];
                foreach ($rows as $row) {
                    $courseid = (int)$row->courseid;
                    if (!isset($result[$courseid])) {
                        $result[$courseid] = (object)[
                            'coursemoduleid' =>
                                (int)$row->coursemoduleid,

                            'timemodified' =>
                                (int)$row->timemodified,
                        ];
                    }
                }

                return $result;
            }
        );

        return CustomerSuccessRepositoryProfiler::measure(
            'moodle_course_progress',
            $userid,
            'result_normalization',
            function () use (
                $courseids,
                $records,
                $latestactivity
            ): array {
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

                    $coursecompletedat =
                        $record !== null
                            ? (int)$record->coursecompletedat
                            : 0;

                    $progress = $tracked > 0
                        ? round(
                            (
                                $completed /
                                $tracked
                            ) * 100,
                            2
                        )
                        : null;

                    $result[$courseid] = (object)[
                        'courseid' => $courseid,

                        'trackedactivities' =>
                            $tracked,

                        'completedactivities' =>
                            $completed,

                        'remainingactivities' =>
                            max(
                                0,
                                $tracked - $completed
                            ),

                        'progresspercentage' =>
                            $progress,

                        'lastcompletionat' =>
                            $record !== null
                                ? (int)$record
                                    ->lastcompletionat
                                : 0,

                        'lastcoursemoduleid' =>
                            (int)(
                                $latestactivity[$courseid]
                                    ->coursemoduleid
                                ?? 0
                            ),

                        'lastactivityat' =>
                            (int)(
                                $latestactivity[$courseid]
                                    ->timemodified
                                ?? 0
                            ),

                        'coursecompleted' =>
                            $coursecompletedat > 0,

                        'coursecompletedat' =>
                            $coursecompletedat,
                    ];
                }

                return $result;
            }
        );
    }

}