<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised lifecycle statuses for Commerce purchases.
 *
 * Legacy subscription and digital purchase statuses are mapped to this
 * provider-independent Commerce lifecycle.
 */
final class CommercePurchaseStatus {

    public const CREATED = 'created';

    public const PREPARED = 'prepared';

    public const PAYMENT_PENDING = 'payment_pending';

    public const AUTHORIZED = 'authorized';

    public const CAPTURED = 'captured';

    public const FULFILLED = 'fulfilled';

    public const CANCELLED = 'cancelled';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const UNKNOWN = 'unknown';

    /**
     * Supported normalised statuses.
     */
    private const VALID_STATUSES = [
        self::CREATED,
        self::PREPARED,
        self::PAYMENT_PENDING,
        self::AUTHORIZED,
        self::CAPTURED,
        self::FULFILLED,
        self::CANCELLED,
        self::FAILED,
        self::REFUNDED,
        self::UNKNOWN,
    ];

    /**
     * Normalise a Legacy purchase status.
     *
     * The payment state is used as a fallback when the Legacy purchase status
     * alone is not sufficiently explicit.
     *
     * @param string $status Legacy or Commerce status.
     * @param CommercePayment|null $payment Associated payment.
     * @return string
     */
    public static function normalise(
        string $status,
        ?CommercePayment $payment = null
    ): string {
        $status =
            strtolower(
                trim($status)
            );

        if (
            in_array(
                $status,
                [
                    self::CREATED,
                    'new',
                    'draft',
                ],
                true
            )
        ) {
            return self::CREATED;
        }

        if (
            in_array(
                $status,
                [
                    self::PREPARED,
                    'ready',
                ],
                true
            )
        ) {
            return self::PREPARED;
        }

        if (
            in_array(
                $status,
                [
                    self::PAYMENT_PENDING,
                    'pending',
                    'queued',
                    'waiting',
                    'processing',
                    'unconfirmed',
                ],
                true
            )
        ) {
            return self::PAYMENT_PENDING;
        }

        if (
            in_array(
                $status,
                [
                    self::AUTHORIZED,
                    'authorised',
                ],
                true
            )
        ) {
            return self::AUTHORIZED;
        }

        if (
            in_array(
                $status,
                [
                    self::CAPTURED,
                    'captured',
                ],
                true
            )
        ) {
            return self::CAPTURED;
        }

        if (
            in_array(
                $status,
                [
                    self::FULFILLED,
                    'active',
                    'paid',
                    'completed',
                    'fulfilled',
                    'expired',
                    'delivered',
                ],
                true
            )
        ) {
            return self::FULFILLED;
        }

        if (
            in_array(
                $status,
                [
                    self::CANCELLED,
                    'canceled',
                    'cancel',
                ],
                true
            )
        ) {
            return self::CANCELLED;
        }

        if (
            in_array(
                $status,
                [
                    self::FAILED,
                    'error',
                    'declined',
                    'rejected',
                ],
                true
            )
        ) {
            return self::FAILED;
        }

        if (
            in_array(
                $status,
                [
                    self::REFUNDED,
                    'partially_refunded',
                ],
                true
            )
        ) {
            return self::REFUNDED;
        }

        if ($payment?->is_successful()) {
            return self::CAPTURED;
        }

        if ($payment?->is_pending()) {
            return self::PAYMENT_PENDING;
        }

        if ($payment?->is_failed()) {
            return self::FAILED;
        }

        return self::UNKNOWN;
    }

    /**
     * Whether a status is a supported normalised status.
     *
     * @param string $status Status.
     * @return bool
     */
    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            strtolower(
                trim($status)
            ),
            self::VALID_STATUSES,
            true
        );
    }

    /**
     * Return all supported statuses.
     *
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_STATUSES;
    }

    /**
     * Whether a purchase is in a final status.
     *
     * @param string $status Status.
     * @return bool
     */
    public static function is_final(
        string $status
    ): bool {
        $status =
            self::normalise(
                $status
            );

        return in_array(
            $status,
            [
                self::FULFILLED,
                self::CANCELLED,
                self::FAILED,
                self::REFUNDED,
            ],
            true
        );
    }

    /**
     * Whether a purchase has been financially successful.
     *
     * @param string $status Status.
     * @return bool
     */
    public static function is_financially_successful(
        string $status
    ): bool {
        $status =
            self::normalise(
                $status
            );

        return in_array(
            $status,
            [
                self::CAPTURED,
                self::FULFILLED,
                self::REFUNDED,
            ],
            true
        );
    }
}