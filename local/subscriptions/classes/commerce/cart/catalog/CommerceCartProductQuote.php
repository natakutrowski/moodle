<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\catalog;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicy;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Catalogue facts required to validate and calculate one cart line. */
final class CommerceCartProductQuote {
    public function __construct(
        private readonly string $productsku,
        private readonly int $priceid,
        private readonly string $displayname,
        private readonly CommerceMoney $unitprice,
        private readonly CommerceQuantityPolicy $quantitypolicy,
        private readonly string $producttype = ''
    ) {
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_price_id(): int {
        return $this->priceid;
    }

    public function get_display_name(): string {
        return $this->displayname;
    }

    public function get_unit_price(): CommerceMoney {
        return $this->unitprice;
    }

    public function get_quantity_policy(): CommerceQuantityPolicy {
        return $this->quantitypolicy;
    }

    public function get_product_type(): string {
        return $this->producttype;
    }
}
