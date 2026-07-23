<?php

namespace local_subscriptions\commerce\postpayment;

defined('MOODLE_INTERNAL') || die();

/**
 * Decision returned before the Legacy post-payment handler is invoked.
 */
final class CommercePostPaymentProcessingResult {

    public const STATUS_LEGACY_REQUIRED = 'legacy_required';
    public const STATUS_COMMERCE_COMPLETED = 'commerce_completed';
    public const STATUS_ALREADY_PROCESSED = 'already_processed';
    public const STATUS_UNSUPPORTED = 'unsupported';

    public function __construct(
        private readonly string $status,
        private readonly ?int $paymentrequestid = null,
        private readonly array $metadata = []
    ) {
    }

    public static function legacy_required(?int $paymentrequestid = null, array $metadata = []): self {
        return new self(self::STATUS_LEGACY_REQUIRED, $paymentrequestid, $metadata);
    }

    public static function commerce_completed(int $paymentrequestid, array $metadata = []): self {
        return new self(self::STATUS_COMMERCE_COMPLETED, $paymentrequestid, $metadata);
    }

    public static function already_processed(int $paymentrequestid, array $metadata = []): self {
        return new self(self::STATUS_ALREADY_PROCESSED, $paymentrequestid, $metadata);
    }

    public static function unsupported(array $metadata = []): self {
        return new self(self::STATUS_UNSUPPORTED, null, $metadata);
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_payment_request_id(): ?int {
        return $this->paymentrequestid;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function requires_legacy(): bool {
        return $this->status === self::STATUS_LEGACY_REQUIRED;
    }

    public function is_handled(): bool {
        return in_array($this->status, [
            self::STATUS_COMMERCE_COMPLETED,
            self::STATUS_ALREADY_PROCESSED,
        ], true);
    }
}
