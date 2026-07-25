<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Result of one Legacy/native shadow read. */
final class CommerceNativeReadResult {
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_EQUAL = 'equal';
    public const STATUS_MISSING_NATIVE = 'missing_native';
    public const STATUS_DIFFERENT = 'different';
    public const STATUS_INVALID_LEGACY = 'invalid_legacy';

    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $status,
        private readonly ?CommercePurchasePersistenceSnapshot $snapshot,
        private readonly array $differences = [],
        private readonly float $legacydurationms = 0.0,
        private readonly float $nativedurationms = 0.0,
        private readonly ?string $message = null
    ) {
        if (!in_array($status, [
            self::STATUS_DISABLED,
            self::STATUS_EQUAL,
            self::STATUS_MISSING_NATIVE,
            self::STATUS_DIFFERENT,
            self::STATUS_INVALID_LEGACY,
        ], true)) {
            throw new \coding_exception('Invalid native Commerce read status.');
        }
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_status(): string { return $this->status; }
    public function get_snapshot(): ?CommercePurchasePersistenceSnapshot { return $this->snapshot; }
    public function get_differences(): array { return $this->differences; }
    public function get_legacy_duration_ms(): float { return $this->legacydurationms; }
    public function get_native_duration_ms(): float { return $this->nativedurationms; }
    public function get_message(): ?string { return $this->message; }
    public function is_equal(): bool { return $this->status === self::STATUS_EQUAL; }
    public function has_issue(): bool {
        return in_array($this->status, [self::STATUS_MISSING_NATIVE, self::STATUS_DIFFERENT, self::STATUS_INVALID_LEGACY], true);
    }
}
