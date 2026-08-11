<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\ownership;

defined('MOODLE_INTERNAL') || die();

/** Cart-facing boundary for effective product ownership. */
interface CommerceCartOwnershipGateway {
    public function owns(int $customerid, string $productsku): bool;
}
