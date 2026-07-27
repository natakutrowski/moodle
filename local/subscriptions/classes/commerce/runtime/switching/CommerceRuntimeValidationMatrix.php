<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

/**
 * Documents the functional scenarios required before enabling Native runtime.
 */
final class CommerceRuntimeValidationMatrix {
    /**
     * @return array<string, array<int, string>>
     */
    public static function scenarios(): array {
        return [
            'subscription' => [
                'stripe_checkout_completed',
                'alfa_checkout_completed',
                'manual_purchase',
                'free_purchase',
                'upgrade_purchase',
            ],
            'digital' => [
                'stripe_checkout_completed',
                'manual_purchase',
                'free_purchase',
            ],
            'resilience' => [
                'payment_failed',
                'checkout_expired',
                'duplicate_webhook',
                'retry',
                'multiple_grants',
                'bundle',
            ],
            'runtime' => [
                'legacy_authoritative',
                'shadow_legacy_authoritative',
                'native_authoritative',
                'native_fallback_enabled',
                'native_fallback_disabled',
                'rollback_to_legacy',
                'single_authoritative_execution',
            ],
        ];
    }

    public static function count(): int {
        return array_sum(array_map('count', self::scenarios()));
    }
}
