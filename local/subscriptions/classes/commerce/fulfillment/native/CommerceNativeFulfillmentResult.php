<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Normalized outcome of one Native Commerce grant fulfillment attempt.
 */
final class CommerceNativeFulfillmentResult {
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    private const STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_SKIPPED,
        self::STATUS_FAILED,
    ];

    private readonly string $status;

    public function __construct(
        private readonly CommerceEntitlementGrant $grant,
        string $status,
        private readonly array $payload = [],
        private readonly ?string $message = null,
        private readonly ?string $errorclass = null
    ) {
        $status = strtolower(trim($status));

        if (!in_array($status, self::STATUSES, true)) {
            throw new \coding_exception('Invalid Native Commerce fulfillment result status.');
        }

        if ($status === self::STATUS_FAILED && trim((string) $message) === '') {
            throw new \coding_exception('A failed Native Commerce fulfillment result requires a message.');
        }

        $this->status = $status;
    }

    public static function completed(
        CommerceEntitlementGrant $grant,
        array $payload = [],
        ?string $message = null
    ): self {
        return new self($grant, self::STATUS_COMPLETED, $payload, $message);
    }

    public static function skipped(
        CommerceEntitlementGrant $grant,
        string $message,
        array $payload = []
    ): self {
        return new self($grant, self::STATUS_SKIPPED, $payload, $message);
    }

    public static function failed(
        CommerceEntitlementGrant $grant,
        string $message,
        ?string $errorclass = null,
        array $payload = []
    ): self {
        return new self($grant, self::STATUS_FAILED, $payload, $message, $errorclass);
    }

    public function get_grant(): CommerceEntitlementGrant {
        return $this->grant;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function is_completed(): bool {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function is_skipped(): bool {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function is_failed(): bool {
        return $this->status === self::STATUS_FAILED;
    }

    public function is_successful(): bool {
        return !$this->is_failed();
    }

    public function get_payload(): array {
        return $this->payload;
    }

    public function get_message(): ?string {
        return $this->message;
    }

    public function get_error_class(): ?string {
        return $this->errorclass;
    }
}
