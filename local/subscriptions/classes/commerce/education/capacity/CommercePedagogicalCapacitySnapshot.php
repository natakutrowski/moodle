<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\capacity;

defined('MOODLE_INTERNAL') || die();

final class CommercePedagogicalCapacitySnapshot {
    public function __construct(
        private readonly string $productsku,
        private readonly bool $pedagogicallinked,
        private readonly bool $salesopen,
        private readonly bool $available,
        private readonly ?string $blockingreason,
        private readonly ?int $promotionid,
        private readonly ?int $productid,
        private readonly ?int $promotioncapacity,
        private readonly int $promotionoccupied,
        private readonly int $promotionreserved,
        private readonly ?int $promotionremaining,
        private readonly ?int $offercapacity,
        private readonly int $offeroocupied,
        private readonly int $offerreserved,
        private readonly ?int $offerremaining,
        private readonly bool $groupsenabled,
        private readonly ?int $groupcapacity,
        private readonly int $groupoccupied,
        private readonly ?int $groupremaining,
        private readonly ?int $remaining
    ) {
        if ($promotionreserved < 0 || $offerreserved < 0) {
            throw new \coding_exception(
                'Reserved pedagogical quantity cannot be negative.'
            );
        }
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function is_pedagogically_linked(): bool {
        return $this->pedagogicallinked;
    }

    public function are_sales_open(): bool {
        return $this->salesopen;
    }

    public function is_available(): bool {
        return $this->available;
    }

    public function get_blocking_reason(): ?string {
        return $this->blockingreason;
    }

    public function get_promotion_id(): ?int {
        return $this->promotionid;
    }

    public function get_product_id(): ?int {
        return $this->productid;
    }

    public function get_promotion_capacity(): ?int {
        return $this->promotioncapacity;
    }

    public function get_promotion_occupied(): int {
        return $this->promotionoccupied;
    }

    public function get_promotion_reserved_quantity(): int {
        return $this->promotionreserved;
    }

    public function get_promotion_remaining(): ?int {
        return $this->promotionremaining;
    }

    public function get_offer_capacity(): ?int {
        return $this->offercapacity;
    }

    public function get_offer_occupied(): int {
        return $this->offeroocupied;
    }

    public function get_offer_reserved_quantity(): int {
        return $this->offerreserved;
    }

    /**
     * Compatibility alias introduced by K1.
     *
     * For one product, the most useful reservation figure is the quantity
     * currently held against that exact offer.
     */
    public function get_reserved_quantity(): int {
        return $this->offerreserved;
    }

    public function get_offer_remaining(): ?int {
        return $this->offerremaining;
    }

    public function are_groups_enabled(): bool {
        return $this->groupsenabled;
    }

    public function get_group_capacity(): ?int {
        return $this->groupcapacity;
    }

    public function get_group_occupied(): int {
        return $this->groupoccupied;
    }

    public function get_group_remaining(): ?int {
        return $this->groupremaining;
    }

    /**
     * Effective number of seats that a new cart can reserve right now.
     *
     * null means commercially unlimited by pedagogy.
     */
    public function get_remaining(): ?int {
        return $this->remaining;
    }

    public function is_sold_out(): bool {
        return $this->pedagogicallinked
            && $this->salesopen
            && $this->remaining === 0;
    }
}
