<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\domain;

defined('MOODLE_INTERNAL') || die();

/** Immutable Native Commerce promotion definition. */
final class CommercePromotion {
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    /**
     * @param string[] $productskus
     * @param string[] $producttypes
     */
    public function __construct(
        private readonly ?int $id,
        private readonly string $name,
        private readonly ?string $code,
        private readonly string $discounttype,
        private readonly int $discountvalue,
        private readonly ?string $currency,
        private readonly int $minimumcartminor,
        private readonly ?int $startsat,
        private readonly ?int $endsat,
        private readonly bool $active,
        private readonly bool $automatic,
        private readonly bool $stackable,
        private readonly int $priority,
        private readonly ?int $globalusagelimit,
        private readonly ?int $userusagelimit,
        private readonly array $productskus = [],
        private readonly array $producttypes = [],
        private readonly array $metadata = []
    ) {
        if (trim($name) === '') {
            throw new \coding_exception('A Commerce promotion name is required.');
        }
        if (!in_array($discounttype, [self::TYPE_PERCENTAGE, self::TYPE_FIXED], true)) {
            throw new \coding_exception('Unsupported Commerce promotion discount type.');
        }
        if ($discountvalue <= 0) {
            throw new \coding_exception('A Commerce promotion discount value must be positive.');
        }
        if ($discounttype === self::TYPE_PERCENTAGE && $discountvalue > 10000) {
            throw new \coding_exception('A percentage Commerce promotion cannot exceed 100 percent.');
        }
        if ($minimumcartminor < 0) {
            throw new \coding_exception('A Commerce promotion minimum cart amount cannot be negative.');
        }
        if ($startsat !== null && $endsat !== null && $endsat <= $startsat) {
            throw new \coding_exception('A Commerce promotion end date must be after its start date.');
        }
        if (!$automatic && ($code === null || trim($code) === '')) {
            throw new \coding_exception('A manual Commerce promotion requires a code.');
        }
    }

    public function get_id(): ?int { return $this->id; }
    public function get_name(): string { return $this->name; }
    public function get_code(): ?string { return $this->code === null ? null : strtoupper(trim($this->code)); }
    public function get_discount_type(): string { return $this->discounttype; }
    /** Percentage is stored in basis points; fixed amount in minor currency units. */
    public function get_discount_value(): int { return $this->discountvalue; }
    public function get_currency(): ?string { return $this->currency === null ? null : strtoupper($this->currency); }
    public function get_minimum_cart_minor(): int { return $this->minimumcartminor; }
    public function get_starts_at(): ?int { return $this->startsat; }
    public function get_ends_at(): ?int { return $this->endsat; }
    public function is_active(): bool { return $this->active; }
    public function is_automatic(): bool { return $this->automatic; }
    public function is_stackable(): bool { return $this->stackable; }
    public function get_priority(): int { return $this->priority; }
    public function get_global_usage_limit(): ?int { return $this->globalusagelimit; }
    public function get_user_usage_limit(): ?int { return $this->userusagelimit; }
    /** @return string[] */
    public function get_product_skus(): array { return $this->productskus; }
    /** @return string[] */
    public function get_product_types(): array { return $this->producttypes; }
    public function get_metadata(): array { return $this->metadata; }
}
