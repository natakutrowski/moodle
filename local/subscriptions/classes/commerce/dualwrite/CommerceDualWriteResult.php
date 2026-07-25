<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

final class CommerceDualWriteResult {
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_CREATED = 'created';
    public const STATUS_UPDATED = 'updated';
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $status,
        private readonly ?string $purchaseuuid = null,
        private readonly array $differences = [],
        private readonly ?string $errormessage = null
    ) {
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_status(): string { return $this->status; }
    public function get_purchase_uuid(): ?string { return $this->purchaseuuid; }
    public function get_differences(): array { return $this->differences; }
    public function get_error_message(): ?string { return $this->errormessage; }
    public function is_successful(): bool {
        return in_array($this->status, [self::STATUS_CREATED, self::STATUS_UPDATED, self::STATUS_UNCHANGED], true);
    }
}
