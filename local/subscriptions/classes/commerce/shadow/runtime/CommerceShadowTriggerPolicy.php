<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

/** Decides whether a dual-write trigger represents a terminal state worth comparing. */
final class CommerceShadowTriggerPolicy {
    public static function should_observe(string $family, string $trigger): bool {
        $family = strtolower(trim($family));
        $trigger = strtolower(trim($trigger));

        if ($family === 'digital') {
            // Checkout persistence happens before payment confirmation. Observing it would
            // compare an unpaid Legacy request with an already-prepared Native entitlement.
            if ($trigger === 'digital_checkout_persisted' || str_contains($trigger, 'payment_failed')) {
                return false;
            }

            return str_contains($trigger, 'completed')
                || str_contains($trigger, 'already_processed')
                || str_contains($trigger, 'reconciliation')
                || str_contains($trigger, 'repair')
                || str_contains($trigger, 'email')
                || str_contains($trigger, 'token');
        }

        if ($family === 'subscription') {
            return str_contains($trigger, 'completed')
                || str_contains($trigger, 'already_processed')
                || str_contains($trigger, 'repair')
                || str_contains($trigger, 'manual')
                || str_contains($trigger, 'admin');
        }

        return false;
    }
}
