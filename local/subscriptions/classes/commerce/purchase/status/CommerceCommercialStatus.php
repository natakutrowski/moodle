<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\status;

defined('MOODLE_INTERNAL') || die();

/** CRM-facing commercial statuses, independent from provider terminology. */
final class CommerceCommercialStatus {
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const TO_FULFILL = 'to_fulfill';
    public const PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const FULFILLED = 'fulfilled';
    public const PAYMENT_FAILED = 'payment_failed';
    public const REFUNDED = 'refunded';
    public const CANCELLED = 'cancelled';
    public const REPLACED = 'replaced';
    public const UNKNOWN = 'unknown';

    public static function all(): array {
        return [
            self::PENDING,
            self::PAID,
            self::TO_FULFILL,
            self::PARTIALLY_FULFILLED,
            self::FULFILLED,
            self::PAYMENT_FAILED,
            self::REFUNDED,
            self::CANCELLED,
            self::REPLACED,
            self::UNKNOWN,
        ];
    }
}
