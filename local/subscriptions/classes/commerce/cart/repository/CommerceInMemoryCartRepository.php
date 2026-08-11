<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/** Volatile repository for tests and non-session boundaries. */
final class CommerceInMemoryCartRepository implements CommerceCartRepository {
    /** @var array<string, array> */
    private array $records = [];

    public function find(string $key): ?CommerceCart {
        return isset($this->records[$key])
            ? CommerceCart::from_array($this->records[$key])
            : null;
    }

    public function save(string $key, CommerceCart $cart): void {
        $this->records[$key] = $cart->to_array();
    }

    public function delete(string $key): void {
        unset($this->records[$key]);
    }
}
