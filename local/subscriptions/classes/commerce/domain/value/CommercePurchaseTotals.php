<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable financial totals of a Commerce purchase.
 *
 * Subtotal is the sum before purchase-item discounts, discount is the total
 * reduction, and total is the resulting amount payable by the customer.
 */
final class CommercePurchaseTotals {

    public function __construct(
        private readonly CommerceMoney $subtotal,
        private readonly CommerceMoney $discount,
        private readonly CommerceMoney $total
    ) {
        $currency = $subtotal->get_currency();

        if (
            $discount->get_currency() !== $currency
            || $total->get_currency() !== $currency
        ) {
            throw new \coding_exception(
                'Commerce purchase totals must use one currency.'
            );
        }

        if ($discount->is_greater_than($subtotal)) {
            throw new \coding_exception(
                'A Commerce purchase discount cannot exceed its subtotal.'
            );
        }

        if (!$subtotal->subtract($discount)->equals($total)) {
            throw new \coding_exception(
                'Commerce purchase totals are financially inconsistent.'
            );
        }
    }

    /**
     * Calculate totals from one or more immutable purchase items.
     *
     * @param CommercePurchaseItem[] $items Purchase lines.
     * @return self
     */
    public static function from_items(array $items): self {
        if ($items === []) {
            throw new \coding_exception(
                'Commerce purchase totals require at least one purchase item.'
            );
        }

        $firstitem = reset($items);

        if (!$firstitem instanceof CommercePurchaseItem) {
            throw new \coding_exception(
                'Every Commerce purchase total item must be a CommercePurchaseItem.'
            );
        }

        $currency = $firstitem->get_currency();
        $subtotal = CommerceMoney::zero($currency);
        $discount = CommerceMoney::zero($currency);
        $total = CommerceMoney::zero($currency);

        foreach ($items as $item) {
            if (!$item instanceof CommercePurchaseItem) {
                throw new \coding_exception(
                    'Every Commerce purchase total item must be a CommercePurchaseItem.'
                );
            }

            if ($item->get_currency() !== $currency) {
                throw new \coding_exception(
                    'All Commerce purchase items must use one currency.'
                );
            }

            $subtotal = $subtotal->add(
                $item->get_gross_amount()
            );

            $discount = $discount->add(
                $item->get_discount()
            );

            $total = $total->add(
                $item->get_net_amount()
            );
        }

        return new self(
            $subtotal,
            $discount,
            $total
        );
    }

    public static function zero(string $currency): self {
        $zero = CommerceMoney::zero($currency);

        return new self(
            $zero,
            $zero,
            $zero
        );
    }

    public function get_subtotal(): CommerceMoney {
        return $this->subtotal;
    }

    public function get_discount(): CommerceMoney {
        return $this->discount;
    }

    public function get_total(): CommerceMoney {
        return $this->total;
    }

    public function get_currency(): string {
        return $this->subtotal->get_currency();
    }

    public function has_discount(): bool {
        return !$this->discount->is_zero();
    }

    public function is_free(): bool {
        return $this->total->is_zero();
    }

    /**
     * Return a serialisable financial representation.
     *
     * @return array<string, array{amountminor: int, currency: string}>
     */
    public function to_array(): array {
        return [
            'subtotal' => $this->subtotal->to_array(),
            'discount' => $this->discount->to_array(),
            'total' => $this->total->to_array(),
        ];
    }
}
