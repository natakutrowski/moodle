<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical payment-status rules used by CRM metrics.
 */
final class PaymentStatus {

    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    public const CANCELED = 'canceled';
    public const EXPIRED = 'expired';
    public const ERROR = 'error';

    /**
     * Statuses proving that a payment was completed.
     *
     * @return string[]
     */
    public static function successful(): array {
        return [
            self::PAID,
            self::COMPLETED,
        ];
    }

    /**
     * Normalize a payment status coming from the database or a provider.
     */
    public static function normalize(?string $status): string {
        return strtolower(trim((string)$status));
    }

    /**
     * Whether the supplied payment status proves a completed payment.
     */
    public static function is_successful(?string $status): bool {
        return in_array(
            self::normalize($status),
            self::successful(),
            true
        );
    }

    /**
     * SQL fragment matching successful payment statuses.
     *
     * The field name must be a trusted internal SQL identifier.
     */
    public static function successful_sql(string $field): string {
        return "LOWER({$field}) IN ('paid', 'completed')";
    }
}