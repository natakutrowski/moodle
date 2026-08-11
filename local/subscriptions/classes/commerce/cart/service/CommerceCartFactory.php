<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/** Creates empty carts without coupling the domain to Moodle. */
final class CommerceCartFactory {
    public function create(int $customerid, string $currency, array $metadata = []): CommerceCart {
        $now = time();

        return new CommerceCart(
            bin2hex(random_bytes(16)),
            $customerid,
            $currency,
            [],
            $metadata,
            $now,
            $now
        );
    }
}
