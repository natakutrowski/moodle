<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\library\CommerceCourseAccessPresentation;

/** Immutable presentation data for one course shown on the My courses page. */
final class MyCoursePresentation {
    public function __construct(
        public readonly \stdClass $course,
        public readonly ?float $progress,
        public readonly ?int $completedactivities,
        public readonly ?int $totalactivities,
        public readonly bool $completed,
        public readonly bool $trial,
        public readonly CommerceCourseAccessPresentation $commerceaccess
    ) {
        if (empty($course->id) || (int)$course->id <= 0) {
            throw new \coding_exception('A My courses presentation requires a valid course record.');
        }
        if ($progress !== null && ($progress < 0.0 || $progress > 100.0)) {
            throw new \coding_exception('Course progress must be between 0 and 100.');
        }
    }

    public function courseid(): int {
        return (int)$this->course->id;
    }

    public function categoryid(): int {
        return (int)($this->course->category ?? 0);
    }
}
