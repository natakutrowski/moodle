<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\domain;

defined('MOODLE_INTERNAL') || die();

/** Result of evaluating a promotion without calculating its discount. */
final class CommercePromotionEligibility {
    private function __construct(
        private readonly bool $eligible,
        private readonly string $reason,
        private readonly array $context = []
    ) {}

    public static function eligible(): self { return new self(true, 'eligible'); }
    public static function rejected(string $reason, array $context = []): self { return new self(false, $reason, $context); }
    public function is_eligible(): bool { return $this->eligible; }
    public function get_reason(): string { return $this->reason; }
    public function get_context(): array { return $this->context; }
}
