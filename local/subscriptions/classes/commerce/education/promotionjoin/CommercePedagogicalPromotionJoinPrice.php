<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Immutable owner-only price for joining one pedagogical promotion offer. */
final class CommercePedagogicalPromotionJoinPrice {
    public function __construct(
        private readonly int $promotionid,
        private readonly int $productid,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?int $id = null
    ) {
        if ($promotionid <= 0 || $productid <= 0) {
            throw new \coding_exception('Promotion join price requires positive promotion and product identifiers.');
        }
        if ($id !== null && $id <= 0) {
            throw new \coding_exception('Promotion join price identifier must be positive.');
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception('Promotion join price currency must use ISO 4217 format.');
        }
        if ($amountminor <= 0) {
            throw new \coding_exception('Promotion join price must be strictly positive.');
        }
    }

    public function get_id(): ?int { return $this->id; }
    public function get_promotion_id(): int { return $this->promotionid; }
    public function get_product_id(): int { return $this->productid; }
    public function get_currency(): string { return strtoupper(trim($this->currency)); }
    public function get_amount_minor(): int { return $this->amountminor; }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'promotionid' => $this->promotionid,
            'productid' => $this->productid,
            'currency' => $this->get_currency(),
            'amountminor' => $this->amountminor,
        ];
    }
}
