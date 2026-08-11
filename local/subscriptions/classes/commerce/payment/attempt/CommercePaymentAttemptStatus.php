<?php

namespace local_subscriptions\commerce\payment\attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised lifecycle statuses for native Commerce payment attempts.
 */
final class CommercePaymentAttemptStatus {

    public const CREATED = 'created';
    public const REDIRECTED = 'redirected';
    public const PAID = 'paid';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    /** Historical statuses accepted while the Legacy bridge still exists. */
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const ERROR = 'error';
    public const UNKNOWN = 'unknown';

    private const VALID = [
        self::CREATED,
        self::REDIRECTED,
        self::PAID,
        self::FAILED,
        self::CANCELLED,
        self::REFUNDED,
        self::PENDING,
        self::COMPLETED,
        self::ERROR,
        self::UNKNOWN,
    ];

    /**
     * Normalise and validate one payment status.
     */
    public static function normalise(string $status): string {
        $status = strtolower(trim($status));

        if (!in_array($status, self::VALID, true)) {
            throw new \InvalidArgumentException(
                'Unsupported Commerce payment attempt status: ' . $status
            );
        }

        return $status;
    }

    /**
     * Whether the status is terminal for the current payment attempt.
     */
    public static function is_terminal(string $status): bool {
        return in_array(self::normalise($status), [
            self::PAID,
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::ERROR,
        ], true);
    }

    private function __construct() {
    }
}
