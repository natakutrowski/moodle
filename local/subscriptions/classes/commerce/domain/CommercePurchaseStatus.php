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

    public const DRAFT = 'draft';

    public const CREATED = 'created';

    public const PREPARED = 'prepared';

    public const PAYMENT_PENDING = 'payment_pending';

    public const AUTHORIZED = 'authorized';

    public const CAPTURED = 'captured';

    public const PAID = 'paid';

    public const FULFILLMENT_PENDING = 'fulfillment_pending';

    public const FULFILLED = 'fulfilled';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const UNKNOWN = 'unknown';

    /**
     * Supported normalised statuses.
     */
    private const VALID_STATUSES = [
        self::DRAFT,
        self::CREATED,
        self::PREPARED,
        self::PAYMENT_PENDING,
        self::AUTHORIZED,
        self::CAPTURED,
        self::PAID,
        self::FULFILLMENT_PENDING,
        self::FULFILLED,
        self::COMPLETED,
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
                    self::DRAFT,
                    self::CREATED,
                    'new',
                ],
                true
            )
        ) {
            return $status === self::DRAFT ? self::DRAFT : self::CREATED;
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

        if ($status === self::PAID) {
            return self::PAID;
        }

        if ($status === self::FULFILLMENT_PENDING) {
            return self::FULFILLMENT_PENDING;
        }

        if ($status === self::COMPLETED) {
            return self::COMPLETED;
        }

        if (
            in_array(
                $status,
                [
                    self::FULFILLED,
                    'active',
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
                self::COMPLETED,
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
                self::PAID,
                self::FULFILLMENT_PENDING,
                self::FULFILLED,
                self::COMPLETED,
                self::REFUNDED,
            ],
            true
        );
    }

    /**
     * Whether the aggregate may move from one lifecycle status to another.
     */
    public static function can_transition(string $from, string $to): bool {
        $from = self::normalise($from);
        $to = self::normalise($to);

        if ($from === $to) {
            return true;
        }

        $transitions = [
            self::DRAFT => [self::PREPARED, self::CANCELLED],
            self::CREATED => [self::PREPARED, self::PAYMENT_PENDING, self::CANCELLED],
            self::PREPARED => [self::PAYMENT_PENDING, self::PAID, self::CANCELLED],
            self::PAYMENT_PENDING => [self::PAID, self::CANCELLED],
            self::AUTHORIZED => [self::PAID, self::CAPTURED, self::CANCELLED],
            self::CAPTURED => [self::PAID, self::FULFILLMENT_PENDING, self::FULFILLED],
            self::PAID => [self::FULFILLMENT_PENDING, self::FULFILLED, self::COMPLETED, self::REFUNDED],
            self::FULFILLMENT_PENDING => [self::FULFILLED, self::CANCELLED, self::REFUNDED],
            self::FULFILLED => [self::COMPLETED, self::REFUNDED],
            self::COMPLETED => [self::REFUNDED],
            self::FAILED => [self::PAYMENT_PENDING, self::CANCELLED],
            self::UNKNOWN => [self::PREPARED, self::PAYMENT_PENDING, self::PAID, self::FULFILLED, self::CANCELLED],
            self::CANCELLED => [],
            self::REFUNDED => [],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }

    /** Whether purchase lines are still editable. */
    public static function is_editable(string $status): bool {
        return in_array(self::normalise($status), [self::DRAFT, self::CREATED], true);
    }
}