<?php

namespace local_subscriptions\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle statuses of a Commerce purchase request.
 *
 * These statuses describe the Commerce workflow and are independent
 * from payment-provider statuses.
 */
final class CommercePurchaseRequestStatus {

    public const DRAFT = 'draft';
    public const VALIDATED = 'validated';
    public const PAYMENT_PENDING = 'payment_pending';
    public const PAYMENT_CONFIRMED = 'payment_confirmed';
    public const FULFILLMENT_PENDING = 'fulfillment_pending';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::VALIDATED,
        self::PAYMENT_PENDING,
        self::PAYMENT_CONFIRMED,
        self::FULFILLMENT_PENDING,
        self::COMPLETED,
        self::REJECTED,
        self::FAILED,
        self::CANCELLED,
    ];

    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::VALID_STATUSES,
            true
        );
    }

    public static function normalise(
        string $status
    ): string {
        $status = strtolower(
            trim($status)
        );

        if (!self::is_valid($status)) {
            throw new \coding_exception(
                'Unsupported Commerce purchase request status: ' .
                $status
            );
        }

        return $status;
    }

    public static function is_terminal(
        string $status
    ): bool {
        $status = self::normalise($status);

        return in_array(
            $status,
            [
                self::COMPLETED,
                self::REJECTED,
                self::FAILED,
                self::CANCELLED,
            ],
            true
        );
    }

    /**
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_STATUSES;
    }
}