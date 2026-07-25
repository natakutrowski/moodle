<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\policy;

defined('MOODLE_INTERNAL') || die();

final class CommerceReadDecision {
    public const SOURCE_NATIVE = 'native';
    public const SOURCE_LEGACY_FALLBACK = 'legacy_fallback';
    public const SOURCE_NONE = 'none';

    public function __construct(
        public readonly string $source,
        public readonly mixed $value,
        public readonly bool $shadowcompared = false,
        public readonly ?string $shadowseverity = null,
        public readonly array $differences = []
    ) {
    }

    public function is_success(): bool {
        return $this->value !== null;
    }
}
