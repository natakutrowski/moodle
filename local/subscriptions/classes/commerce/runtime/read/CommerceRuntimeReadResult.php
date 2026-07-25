<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Result of one I7 runtime read decision. */
final class CommerceRuntimeReadResult {
    public const STATUS_OK = 'ok';
    public const STATUS_FALLBACK = 'fallback';
    public const STATUS_MISSING = 'missing';
    public const STATUS_DIFFERENT = 'different';
    public const STATUS_INVALID = 'invalid';

    public const SOURCE_LEGACY = 'legacy';
    public const SOURCE_NATIVE = 'native';
    public const SOURCE_NONE = 'none';

    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $mode,
        private readonly string $status,
        private readonly string $source,
        private readonly ?CommercePurchasePersistenceSnapshot $snapshot,
        private readonly bool $fallbackused = false,
        private readonly array $differences = [],
        private readonly float $legacydurationms = 0.0,
        private readonly float $nativedurationms = 0.0,
        private readonly ?string $message = null
    ) {
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_mode(): string { return $this->mode; }
    public function get_status(): string { return $this->status; }
    public function get_source(): string { return $this->source; }
    public function get_snapshot(): ?CommercePurchasePersistenceSnapshot { return $this->snapshot; }
    public function used_fallback(): bool { return $this->fallbackused; }
    public function get_differences(): array { return $this->differences; }
    public function get_legacy_duration_ms(): float { return $this->legacydurationms; }
    public function get_native_duration_ms(): float { return $this->nativedurationms; }
    public function get_message(): ?string { return $this->message; }
    public function is_success(): bool { return $this->snapshot !== null; }
    public function has_issue(): bool {
        return in_array($this->status, [self::STATUS_FALLBACK, self::STATUS_MISSING, self::STATUS_DIFFERENT, self::STATUS_INVALID], true);
    }
}
