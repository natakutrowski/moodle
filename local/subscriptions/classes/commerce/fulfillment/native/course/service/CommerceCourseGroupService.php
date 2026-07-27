<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Applies optional course group memberships represented by a Native grant. */
interface CommerceCourseGroupService {
    public function apply(int $userid, int $courseid, array $groupids, array $groupnames, bool $dryrun): array;
}
