<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

final class ExpandedCommerceProductItem {
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly int $quantity
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception('Expanded Commerce item quantity must be positive.');
        }
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }
}
