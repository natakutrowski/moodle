<?php

namespace local_subscriptions\digital\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised status returned by a digital payment provider.
 */
final class DigitalPaymentProviderStatus {

    public const PAID = 'PAID';

    public const DECLINED = 'DECLINED';

    public const PENDING = 'PENDING';

    public const UNKNOWN = 'UNKNOWN';

    public const ERROR = 'ERROR';

    private const VALID_STATUSES = [
        self::PAID,
        self::DECLINED,
        self::PENDING,
        self::UNKNOWN,
        self::ERROR,
    ];

    public function __construct(
        public readonly string $status,
        public readonly string $reason = ''
    ) {
        if (
            !in_array(
                $status,
                self::VALID_STATUSES,
                true
            )
        ) {
            throw new \coding_exception(
                'Invalid digital payment provider status: ' .
                $status
            );
        }
    }

    public static function paid(): self {
        return new self(
            self::PAID
        );
    }

    public static function declined(
        string $reason
    ): self {
        return new self(
            self::DECLINED,
            $reason
        );
    }

    public static function pending(
        string $reason = ''
    ): self {
        return new self(
            self::PENDING,
            $reason
        );
    }

    public static function unknown(
        string $reason = ''
    ): self {
        return new self(
            self::UNKNOWN,
            $reason
        );
    }

    public static function error(
        string $reason
    ): self {
        return new self(
            self::ERROR,
            $reason
        );
    }

    public function is_paid(): bool {
        return
            $this->status ===
            self::PAID;
    }

    public function is_declined(): bool {
        return
            $this->status ===
            self::DECLINED;
    }

    public function is_pending(): bool {
        return
            $this->status ===
            self::PENDING;
    }

    public function is_unknown(): bool {
        return
            $this->status ===
            self::UNKNOWN;
    }

    public function is_error(): bool {
        return
            $this->status ===
            self::ERROR;
    }

    public function to_array(): array {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}