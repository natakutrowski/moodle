<?php

namespace local_subscriptions\commerce\payment\result;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent Commerce payment statuses.
 *
 * These statuses must not expose Stripe, Alfa or another provider vocabulary.
 */
final class CommercePaymentStatus {

    /**
     * The payment exists locally but has not yet been sent to a provider.
     */
    public const CREATED = 'created';

    /**
     * The customer must complete an action, generally a redirect.
     */
    public const REQUIRES_ACTION = 'requires_action';

    /**
     * The provider accepted the payment request but has not confirmed it yet.
     */
    public const PENDING = 'pending';

    /**
     * The payment was successfully confirmed.
     */
    public const SUCCEEDED = 'succeeded';

    /**
     * The payment permanently failed.
     */
    public const FAILED = 'failed';

    /**
     * The payment was cancelled before completion.
     */
    public const CANCELLED = 'cancelled';

    /**
     * The payment session or payment request expired.
     */
    public const EXPIRED = 'expired';

    /**
     * The full payment amount was refunded.
     */
    public const REFUNDED = 'refunded';

    /**
     * Part of the payment amount was refunded.
     */
    public const PARTIALLY_REFUNDED =
        'partially_refunded';

    private const VALID_STATUSES = [
        self::CREATED,
        self::REQUIRES_ACTION,
        self::PENDING,
        self::SUCCEEDED,
        self::FAILED,
        self::CANCELLED,
        self::EXPIRED,
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
    ];

    private const TERMINAL_STATUSES = [
        self::SUCCEEDED,
        self::FAILED,
        self::CANCELLED,
        self::EXPIRED,
        self::REFUNDED,
    ];

    private const SUCCESSFUL_STATUSES = [
        self::SUCCEEDED,
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
    ];

    public static function normalise(
        string $status
    ): string {
        $status = strtolower(
            trim($status)
        );

        if (!self::is_valid($status)) {
            throw new \coding_exception(
                'Unsupported Commerce payment status: ' .
                $status
            );
        }

        return $status;
    }

    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            strtolower(trim($status)),
            self::VALID_STATUSES,
            true
        );
    }

    public static function is_terminal(
        string $status
    ): bool {
        return in_array(
            self::normalise($status),
            self::TERMINAL_STATUSES,
            true
        );
    }

    public static function is_successful(
        string $status
    ): bool {
        return in_array(
            self::normalise($status),
            self::SUCCESSFUL_STATUSES,
            true
        );
    }

    public static function requires_customer_action(
        string $status
    ): bool {
        return self::normalise($status)
            === self::REQUIRES_ACTION;
    }

    /**
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_STATUSES;
    }
}