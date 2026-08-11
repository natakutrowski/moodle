<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

/** Read model produced by resolving and calculating a cart. */
final class CommerceCartSnapshot {
    /** @param CommerceCalculatedCartItem[] $items */
    public function __construct(
        private readonly CommerceCart $cart,
        private readonly array $items,
        private readonly CommerceCartTotals $totals,
        private readonly int $calculatedat,
        private readonly array $messages = [],
        private readonly array $promotionadjustments = []
    ) {
        foreach ($items as $item) {
            if (!$item instanceof CommerceCalculatedCartItem) {
                throw new \coding_exception('Invalid calculated Commerce cart item collection.');
            }
        }
        foreach ($messages as $message) {
            if (!$message instanceof CommerceCartMessage) {
                throw new \coding_exception('Invalid Commerce cart snapshot message collection.');
            }
        }
        foreach ($promotionadjustments as $adjustment) {
            if (!$adjustment instanceof \local_subscriptions\commerce\promotion\domain\CommercePromotionAdjustment) {
                throw new \coding_exception('Invalid Commerce promotion adjustment collection.');
            }
        }
    }

    public function get_cart(): CommerceCart {
        return $this->cart;
    }

    /** @return CommerceCalculatedCartItem[] */
    public function get_items(): array {
        return $this->items;
    }

    public function get_totals(): CommerceCartTotals {
        return $this->totals;
    }

    public function get_calculated_at(): int {
        return $this->calculatedat;
    }

    /** @return CommerceCartMessage[] */
    public function get_messages(): array {
        return $this->messages;
    }

    /** @return \local_subscriptions\commerce\promotion\domain\CommercePromotionAdjustment[] */
    public function get_promotion_adjustments(): array {
        return $this->promotionadjustments;
    }
}
