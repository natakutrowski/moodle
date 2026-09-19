<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Price decision for an existing owner joining a pedagogical promotion. */
final class CommercePedagogicalPromotionJoinPricing {
    public const OWNER_PRICE_NOT_CONFIGURED = 'owner_price_not_configured';
    public const CURRENCY_NOT_AVAILABLE = 'promotion_join_currency_not_available';

    public function __construct(
        private readonly bool $purchasable,
        private readonly ?string $reason,
        private readonly string $currency,
        private readonly ?CommercePedagogicalPromotionJoinPrice $price
    ) {
        if ($purchasable && ($reason !== null || $price === null)) {
            throw new \coding_exception('Purchasable promotion join pricing requires a price and no blocking reason.');
        }
    }

    public static function denied(string $reason, string $currency): self {
        return new self(false, trim($reason), strtoupper(trim($currency)), null);
    }

    public static function allowed(CommercePedagogicalPromotionJoinPrice $price): self {
        return new self(true, null, $price->get_currency(), $price);
    }

    public function is_purchasable(): bool { return $this->purchasable; }
    public function get_reason(): ?string { return $this->reason; }
    public function get_currency(): string { return $this->currency; }
    public function get_price(): ?CommercePedagogicalPromotionJoinPrice { return $this->price; }
    public function get_amount_minor(): ?int { return $this->price?->get_amount_minor(); }
    public function get_price_id(): ?int { return $this->price?->get_id(); }

    /** @return array<string,mixed> */
    public function to_array(): array {
        return [
            'purchasable' => $this->purchasable,
            'reason' => $this->reason,
            'currency' => $this->currency,
            'priceid' => $this->get_price_id(),
            'amountminor' => $this->get_amount_minor(),
        ];
    }
}
