<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\domain;

defined('MOODLE_INTERNAL') || die();

/** Read-only input used by promotion eligibility rules. */
final class CommercePromotionEvaluationContext {
    /** @param array<int, array{sku:string,type:string,subtotalminor:int}> $items */
    public function __construct(
        private readonly int $subtotalminor,
        private readonly string $currency,
        private readonly ?int $userid,
        private readonly array $items,
        private readonly int $evaluatedat
    ) {
        if ($subtotalminor < 0) {
            throw new \coding_exception('Promotion evaluation subtotal cannot be negative.');
        }
    }

    public function get_subtotal_minor(): int { return $this->subtotalminor; }
    public function get_currency(): string { return strtoupper($this->currency); }
    public function get_user_id(): ?int { return $this->userid; }
    public function get_items(): array { return $this->items; }
    public function get_evaluated_at(): int { return $this->evaluatedat; }
}
