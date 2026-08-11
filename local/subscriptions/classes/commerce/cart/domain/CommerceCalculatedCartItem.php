<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** One cart line enriched from the current catalogue during calculation. */
final class CommerceCalculatedCartItem {
    public function __construct(
        private readonly CommerceCartItem $item,
        private readonly string $name,
        private readonly CommerceMoney $unitprice,
        private readonly CommerceMoney $subtotal,
        private readonly int $minimumquantity,
        private readonly ?int $maximumquantity,
        private readonly int $quantitystep,
        private readonly string $producttype = ''
    ) {
    }

    public function get_item(): CommerceCartItem {
        return $this->item;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_unit_price(): CommerceMoney {
        return $this->unitprice;
    }

    public function get_subtotal(): CommerceMoney {
        return $this->subtotal;
    }

    public function get_minimum_quantity(): int {
        return $this->minimumquantity;
    }

    public function get_maximum_quantity(): ?int {
        return $this->maximumquantity;
    }

    public function get_quantity_step(): int {
        return $this->quantitystep;
    }

    public function get_product_type(): string {
        return $this->producttype;
    }
}
