<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/** Moodle-session implementation; no SQL persistence is introduced in G1. */
final class CommerceSessionCartRepository implements CommerceCartRepository {
    private const SESSION_PROPERTY = 'local_subscriptions_commerce_carts';

    public function find(string $key): ?CommerceCart {
        global $SESSION;

        $records = (array)($SESSION->{self::SESSION_PROPERTY} ?? []);
        return isset($records[$key])
            ? CommerceCart::from_array((array)$records[$key])
            : null;
    }

    public function save(string $key, CommerceCart $cart): void {
        global $SESSION;

        $records = (array)($SESSION->{self::SESSION_PROPERTY} ?? []);
        $records[$key] = $cart->to_array();
        $SESSION->{self::SESSION_PROPERTY} = $records;
    }

    public function delete(string $key): void {
        global $SESSION;

        $records = (array)($SESSION->{self::SESSION_PROPERTY} ?? []);
        unset($records[$key]);
        $SESSION->{self::SESSION_PROPERTY} = $records;
    }
}
