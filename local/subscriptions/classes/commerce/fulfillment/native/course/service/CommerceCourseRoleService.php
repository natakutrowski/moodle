<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Applies the precise course-context role represented by a Native grant. */
interface CommerceCourseRoleService {
    public function apply(int $userid, int $courseid, string $roleshortname, bool $dryrun): array;
}
