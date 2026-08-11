<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

/** Stable session slot for one customer and currency. */
final class CommerceCartSessionKeyResolver {
    public function resolve(int $customerid, string $currency): string {
        if ($customerid < 0) {
            throw new \coding_exception('A Commerce cart customer identifier cannot be negative.');
        }

        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('A Commerce cart currency must use ISO 4217 format.');
        }

        return $customerid . ':' . $currency;
    }
}
