<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

/** Extracts a Moodle course id from the supported entitlement formats. */
final class CommerceCourseResourceKeyParser {
    /** @param array<string, mixed> $configuration */
    public static function course_id(string $resourcekey, array $configuration = []): ?int {
        foreach (['courseid', 'course_id'] as $key) {
            $courseid = (int)($configuration[$key] ?? 0);
            if ($courseid > 0) {
                return $courseid;
            }
        }

        $resourcekey = trim($resourcekey);
        if ($resourcekey === '') {
            return null;
        }
        if (ctype_digit($resourcekey)) {
            $courseid = (int)$resourcekey;
            return $courseid > 0 ? $courseid : null;
        }
        if (preg_match('/^course:(\d+)(?::|$)/i', $resourcekey, $matches)) {
            $courseid = (int)$matches[1];
            return $courseid > 0 ? $courseid : null;
        }

        return null;
    }

    private function __construct() {
    }
}
