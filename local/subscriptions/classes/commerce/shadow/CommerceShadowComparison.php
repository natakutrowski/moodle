<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** G3 comparison result between observed Legacy and simulated Native effects. */
final class CommerceShadowComparison {
    public const EQUAL = 'equal';
    public const EQUIVALENT = 'equivalent';
    public const DIFFERENT = 'different';
    public const NOT_COMPARABLE = 'not_comparable';
    public const SHADOW_ERROR = 'shadow_error';

    public function __construct(
        private readonly string $purchasereference,
        private readonly string $status,
        private readonly array $differences = [],
        private readonly array $metadata = []
    ) {
    }

    public function get_status(): string { return $this->status; }
    public function get_differences(): array { return $this->differences; }
    public function is_match(): bool { return in_array($this->status, [self::EQUAL, self::EQUIVALENT], true); }

    public function to_array(): array {
        return [
            'purchasereference' => $this->purchasereference,
            'status' => $this->status,
            'match' => $this->is_match(),
            'differences' => $this->differences,
            'metadata' => $this->metadata,
        ];
    }
}
