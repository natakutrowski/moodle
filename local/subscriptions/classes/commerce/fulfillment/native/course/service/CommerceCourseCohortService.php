<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Applies optional cohort memberships represented by a Native grant. */
interface CommerceCourseCohortService {
    public function apply(int $userid, array $cohortids, array $cohortnames, bool $dryrun): array;
}
