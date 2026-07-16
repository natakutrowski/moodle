<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\MoodleCourseProgressRepository;

/**
 * Collects native Moodle course progress metrics.
 */
final class MoodleCourseProgressCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly MoodleCourseProgressRepository $repository =
            new MoodleCourseProgressRepository()
    ) {
    }

    public function key(): string {
        return 'moodle_course_progress';
    }

    public function is_available(): bool {
        return true;
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {
        $courses =
            $this->repository->get_accessible_courses($userid);

        $courseids = array_map(
            static fn(\stdClass $course): int =>
                (int)$course->id,
            $courses
        );

        $progressrecords =
            $this->repository->get_course_progress_records(
                $userid,
                $courseids
            );

        $metrics = new SuccessMetricCollection();

        $totaltracked = 0;
        $totalcompletedactivities = 0;
        $courseswithtracking = 0;
        $coursesstarted = 0;
        $coursescompleted = 0;
        $progresssum = 0.0;
        $lastprogressat = 0;

        foreach ($courses as $course) {
            $courseid = (int)$course->id;

            $progress =
                $progressrecords[$courseid] ??
                $this->empty_progress($courseid);

            $tracked =
                (int)$progress->trackedactivities;

            $completed =
                (int)$progress->completedactivities;

            $percentage =
                $progress->progresspercentage !== null
                    ? (float)$progress->progresspercentage
                    : null;

            $coursemetadata = [
                'courseid' => $courseid,
                'shortname' =>
                    (string)($course->shortname ?? ''),
                'fullname' =>
                    (string)($course->fullname ?? ''),
                'visible' =>
                    (int)($course->visible ?? 0),
            ];

            $metrics->add(
                $this->metric(
                    $userid,
                    'learning.course.' .
                        $courseid .
                        '.tracked_activities',
                    $tracked,
                    $measuredat,
                    $coursemetadata
                )
            );

            $metrics->add(
                $this->metric(
                    $userid,
                    'learning.course.' .
                        $courseid .
                        '.completed_activities',
                    $completed,
                    $measuredat,
                    $coursemetadata
                )
            );

            $metrics->add(
                $this->metric(
                    $userid,
                    'learning.course.' .
                        $courseid .
                        '.progress_percentage',
                    $percentage,
                    $measuredat,
                    $coursemetadata
                )
            );

            $metrics->add(
                $this->metric(
                    $userid,
                    'learning.course.' .
                        $courseid .
                        '.completed',
                    (bool)$progress->coursecompleted,
                    $measuredat,
                    $coursemetadata
                )
            );

            if ($tracked <= 0) {
                continue;
            }

            $courseswithtracking++;
            $totaltracked += $tracked;
            $totalcompletedactivities += $completed;
            $progresssum += $percentage ?? 0.0;

            if ($completed > 0) {
                $coursesstarted++;
            }

            if ((bool)$progress->coursecompleted) {
                $coursescompleted++;
            }

            $lastprogressat = max(
                $lastprogressat,
                (int)$progress->lastcompletionat,
                (int)$progress->coursecompletedat
            );
        }

        $averageprogress =
            $courseswithtracking > 0
                ? round(
                    $progresssum /
                        $courseswithtracking,
                    2
                )
                : null;

        $overallactivityprogress =
            $totaltracked > 0
                ? round(
                    (
                        $totalcompletedactivities /
                        $totaltracked
                    ) * 100,
                    2
                )
                : null;

        $aggregate = [
            'accessible_course_count' =>
                count($courses),
            'tracked_course_count' =>
                $courseswithtracking,
            'started_course_count' =>
                $coursesstarted,
            'completed_course_count' =>
                $coursescompleted,
            'tracked_activity_count' =>
                $totaltracked,
            'completed_activity_count' =>
                $totalcompletedactivities,
            'average_course_progress_percentage' =>
                $averageprogress,
            'overall_activity_progress_percentage' =>
                $overallactivityprogress,
            'last_progress_at' =>
                $lastprogressat,
        ];

        foreach ($aggregate as $key => $value) {
            $metrics->add(
                $this->metric(
                    $userid,
                    'learning.' . $key,
                    $value,
                    $measuredat,
                    [
                        'aggregate' => true,
                        'course_count' => count($courses),
                    ]
                )
            );
        }

        return $metrics;
    }

    private function metric(
        int $userid,
        string $key,
        int|float|string|bool|null $value,
        int $measuredat,
        array $metadata = []
    ): SuccessMetric {
        return new SuccessMetric(
            $userid,
            $key,
            $value,
            SuccessMetricSource::COURSE_COMPLETION,
            $measuredat,
            $metadata
        );
    }

    private function empty_progress(
        int $courseid
    ): \stdClass {
        return (object)[
            'courseid' => $courseid,
            'trackedactivities' => 0,
            'completedactivities' => 0,
            'remainingactivities' => 0,
            'progresspercentage' => null,
            'lastcompletionat' => 0,
            'coursecompleted' => false,
            'coursecompletedat' => 0,
        ];
    }
}