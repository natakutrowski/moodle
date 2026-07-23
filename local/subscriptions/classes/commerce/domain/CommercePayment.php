<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent representation of a Commerce payment.
 *
 * The payment knows how much was paid, in which currency, through which
 * provider and with which status. It does not need to know how access is
 * granted after the payment.
 */
final class CommercePayment {

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';
    public const STATUS_UNKNOWN = 'unknown';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_ERROR,
        self::STATUS_UNKNOWN,
    ];

    /**
     * @param int $amountminor Amount expressed in the smallest currency unit.
     * @param string $currency ISO currency code.
     * @param string $status Normalised Commerce payment status.
     * @param string|null $provider Payment provider identifier.
     * @param string|null $transactionid Provider transaction identifier.
     * @param int|null $legacyrequestid Historical payment-request identifier.
     * @param int|null $paidat Payment timestamp.
     * @param array $metadata Additional provider-independent information.
     */
    public function __construct(
        private readonly int $amountminor,
        private readonly string $currency,
        private readonly string $status,
        private readonly ?string $provider = null,
        private readonly ?string $transactionid = null,
        private readonly ?int $legacyrequestid = null,
        private readonly ?int $paidat = null,
        private readonly array $metadata = []
    ) {
        if ($amountminor < 0) {
            throw new \coding_exception('A Commerce payment amount cannot be negative.');
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper($currency))) {
            throw new \coding_exception('A Commerce payment currency must be a three-letter ISO code.');
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \coding_exception('Unsupported Commerce payment status: ' . $status);
        }

        if ($legacyrequestid !== null && $legacyrequestid <= 0) {
            throw new \coding_exception('A legacy payment-request identifier must be positive.');
        }
    }

    /**
     * Creates a payment from an amount expressed in major currency units.
     */
    public static function from_major_amount(
        float $amount,
        string $currency,
        string $status,
        ?string $provider = null,
        ?string $transactionid = null,
        ?int $legacyrequestid = null,
        ?int $paidat = null,
        array $metadata = [],
        int $minorunitexponent = 2
    ): self {
        if ($minorunitexponent < 0 || $minorunitexponent > 4) {
            throw new \coding_exception('Unsupported currency minor-unit exponent.');
        }

        $factor = 10 ** $minorunitexponent;
        $amountminor = (int)round($amount * $factor);

        return new self(
            $amountminor,
            strtoupper($currency),
            $status,
            $provider,
            $transactionid,
            $legacyrequestid,
            $paidat,
            $metadata
        );
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_amount_major(int $minorunitexponent = 2): float {
        return $this->amountminor / (10 ** $minorunitexponent);
    }

    public function get_currency(): string {
        return strtoupper($this->currency);
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_provider(): ?string {
        return $this->provider;
    }

    public function get_transaction_id(): ?string {
        return $this->transactionid;
    }

    public function get_legacy_request_id(): ?int {
        return $this->legacyrequestid;
    }

    public function get_paid_at(): ?int {
        return $this->paidat;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function is_successful(): bool {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_COMPLETED,
        ], true);
    }

    public function is_pending(): bool {
        return $this->status === self::STATUS_PENDING;
    }

    public function is_failed(): bool {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_ERROR,
        ], true);
    }
}