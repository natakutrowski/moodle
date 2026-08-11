<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/**
 * Backwards-compatible resolver retained for callers from earlier releases.
 *
 * Mobile course artwork is now owned by local_campus and no longer inferred
 * from Commerce products, plans or bundles.
 */
final class MyCourseCommerceCoverResolver {
    private readonly MyCourseMobileCoverResolver $resolver;

    public function __construct() {
        $this->resolver = new MyCourseMobileCoverResolver();
    }

    public function resolve(int $courseid): ?string {
        return $this->resolver->resolve($courseid);
    }
}
