<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** Persists Native digital download access without using historical payment requests. */
interface CommerceDigitalAccessRepository {
    public function find_by_grant_reference(string $grantreference): ?\stdClass;

    public function grant(
        CommerceEntitlementGrant $grant,
        string $token,
        ?int $maxdownloads,
        int $now
    ): array;
}
