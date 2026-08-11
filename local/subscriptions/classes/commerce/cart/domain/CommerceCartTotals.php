<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Current calculator totals, ready for future discounts and taxes. */
final class CommerceCartTotals {
    public function __construct(
        private readonly CommerceMoney $subtotal,
        private readonly CommerceMoney $discount,
        private readonly CommerceMoney $tax,
        private readonly CommerceMoney $total
    ) {
        $currency = $subtotal->get_currency();
        foreach ([$discount, $tax, $total] as $money) {
            if ($money->get_currency() !== $currency) {
                throw new \coding_exception('Commerce cart totals must use one currency.');
            }
        }
    }

    public function get_subtotal(): CommerceMoney {
        return $this->subtotal;
    }

    public function get_discount(): CommerceMoney {
        return $this->discount;
    }

    public function get_tax(): CommerceMoney {
        return $this->tax;
    }

    public function get_total(): CommerceMoney {
        return $this->total;
    }
}
