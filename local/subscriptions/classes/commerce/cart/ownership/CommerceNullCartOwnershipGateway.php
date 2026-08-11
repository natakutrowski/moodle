<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\ownership;

defined('MOODLE_INTERNAL') || die();

/** Neutral ownership implementation for isolated tests and non-user carts. */
final class CommerceNullCartOwnershipGateway implements CommerceCartOwnershipGateway {
    public function owns(int $customerid, string $productsku): bool {
        return false;
    }
}
