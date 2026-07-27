<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Applies the manual course enrolment represented by a Native grant. */
interface CommerceCourseEnrolmentService {
    public function apply(int $userid, int $courseid, string $roleshortname, int $validfrom, ?int $validuntil, bool $dryrun): array;
}
