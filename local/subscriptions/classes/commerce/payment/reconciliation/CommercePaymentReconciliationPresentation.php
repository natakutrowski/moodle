<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation;

defined('MOODLE_INTERNAL') || die();

/**
 * Human-facing labels for provider reconciliation statuses.
 *
 * Provider/raw values stay untouched in domain objects and logs.
 */
final class CommercePaymentReconciliationPresentation {
    public static function alfa_order_status(?int $status): string {
        if ($status === null) {
            return '—';
        }

        $key = 'commerce_reconciliation_alfa_order_status_' . $status;

        return get_string_manager()->string_exists(
            $key,
            'local_subscriptions'
        )
            ? get_string($key, 'local_subscriptions')
            : (string)$status;
    }

    public static function alfa_payment_state(?string $state): string {
        return self::provider_state(
            'alfa',
            $state
        );
    }

    public static function stripe_checkout_status(?string $status): string {
        return self::provider_state(
            'stripe_checkout',
            $status
        );
    }

    public static function stripe_payment_status(?string $status): string {
        return self::provider_state(
            'stripe_payment',
            $status
        );
    }

    public static function paypal_order_status(?string $status): string {
        return self::provider_state(
            'paypal_order',
            $status
        );
    }

    public static function paypal_capture_status(?string $status): string {
        return self::provider_state(
            'paypal_capture',
            $status
        );
    }

    private static function provider_state(
        string $family,
        ?string $state
    ): string {
        $state = trim((string)$state);

        if ($state === '') {
            return '—';
        }

        $normalised = strtolower(
            preg_replace(
                '/[^a-z0-9]+/i',
                '_',
                $state
            ) ?? $state
        );

        $key = 'commerce_reconciliation_'
            . $family
            . '_status_'
            . trim($normalised, '_');

        if (
            get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $key,
                'local_subscriptions'
            );
        }

        return $state;
    }

    private function __construct() {
    }
}
