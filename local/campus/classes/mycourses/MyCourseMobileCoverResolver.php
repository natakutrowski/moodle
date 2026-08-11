<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/** Resolves the dedicated mobile artwork configured for a Moodle course. */
final class MyCourseMobileCoverResolver {
    public function __construct(
        private readonly MyCourseMobileCoverService $covers = new MyCourseMobileCoverService()
    ) {
    }

    public function resolve(int $courseid): ?string {
        return $this->covers->get_url($courseid);
    }
}
