<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/** Persistence boundary for active carts. */
interface CommerceCartRepository {
    public function find(string $key): ?CommerceCart;

    public function save(string $key, CommerceCart $cart): void;

    public function delete(string $key): void;
}
