<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\reference;

defined('MOODLE_INTERNAL') || die();

/** Builds a stable, non-sensitive public alias for a Native purchase reference. */
final class CommercePublicOrderReference {
    public function from_internal(string $reference, int $timecreated): string {
        $year = (int)userdate($timecreated, '%Y');
        $digest = strtoupper(substr(hash('sha256', trim($reference)), 0, 6));
        return sprintf('CFR-%04d-%s', $year, $digest);
    }
}
