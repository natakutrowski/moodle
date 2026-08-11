<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Public-safe price exposed by the storefront read model. */
final class CommerceStorefrontPrice {
    public function __construct(
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?int $compareamountminor = null,
        private readonly ?int $discountpercentage = null,
        private readonly ?int $promotionend = null,
        private readonly ?int $id = null
    ) {
        if ($amountminor < 0) {
            throw new \coding_exception('A storefront price cannot be negative.');
        }
        if ($compareamountminor !== null && $compareamountminor <= $amountminor) {
            throw new \coding_exception('A storefront comparison price must exceed the sale price.');
        }
    }

    public function get_id(): ?int { return $this->id; }

    public function get_currency(): string {
        return strtoupper($this->currency);
    }

    public function get_amount_minor(): int {
        return $this->amountminor;
    }

    public function get_compare_amount_minor(): ?int {
        return $this->compareamountminor;
    }

    public function get_discount_percentage(): ?int {
        return $this->discountpercentage;
    }

    public function get_promotion_end(): ?int {
        return $this->promotionend;
    }

    public function has_active_promotion(): bool {
        return $this->compareamountminor !== null && $this->discountpercentage !== null;
    }

    /** @return array<string, int|string|null|bool> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'currency' => $this->get_currency(),
            'amountminor' => $this->amountminor,
            'compareamountminor' => $this->compareamountminor,
            'discountpercentage' => $this->discountpercentage,
            'promotionend' => $this->promotionend,
            'haspromotion' => $this->has_active_promotion(),
        ];
    }
}
