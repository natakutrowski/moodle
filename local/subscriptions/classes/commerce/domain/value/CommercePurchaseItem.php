<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;

/**
 * Immutable snapshot of one line purchased through Commerce.
 *
 * The catalog item describes what CampusFR can sell. This value object records
 * what the customer actually bought, in which quantity and at which locked
 * financial conditions. Later catalog or pricing changes cannot alter it.
 */
final class CommercePurchaseItem {

    /**
     * @param CommerceItem $item Purchased catalog item.
     * @param int $quantity Purchased quantity.
     * @param CommerceMoney $unitprice Locked unit price before line discount.
     * @param CommerceMoney|null $discount Locked total discount for the line.
     * @param array $pricingsnapshot Provider-independent pricing information.
     * @param array $fulfillmentsnapshot Information required to deliver the item.
     * @param array $metadata Secondary line information.
     */
    public function __construct(
        private readonly CommerceItem $item,
        private readonly int $quantity,
        private readonly CommerceMoney $unitprice,
        ?CommerceMoney $discount = null,
        private readonly array $pricingsnapshot = [],
        private readonly array $fulfillmentsnapshot = [],
        private readonly array $metadata = []
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception(
                'A Commerce purchase item quantity must be positive.'
            );
        }

        $discount ??= CommerceMoney::zero(
            $unitprice->get_currency()
        );

        if ($discount->get_currency() !== $unitprice->get_currency()) {
            throw new \coding_exception(
                'A Commerce purchase item discount must use the unit price currency.'
            );
        }

        $grossamount = $unitprice->multiply(
            $quantity
        );

        if ($discount->is_greater_than($grossamount)) {
            throw new \coding_exception(
                'A Commerce purchase item discount cannot exceed its gross amount.'
            );
        }

        $this->discount = $discount;
    }

    /** Locked total discount applied to this line. */
    private readonly CommerceMoney $discount;

    public function get_item(): CommerceItem {
        return $this->item;
    }

    public function get_item_type(): string {
        return $this->item->get_type();
    }

    public function get_item_reference(): string {
        return $this->item->get_reference();
    }

    public function get_label(): string {
        return $this->item->get_name();
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_unit_price(): CommerceMoney {
        return $this->unitprice;
    }

    public function get_discount(): CommerceMoney {
        return $this->discount;
    }

    public function get_currency(): string {
        return $this->unitprice->get_currency();
    }

    public function get_gross_amount(): CommerceMoney {
        return $this->unitprice->multiply(
            $this->quantity
        );
    }

    public function get_net_amount(): CommerceMoney {
        return $this->get_gross_amount()->subtract(
            $this->discount
        );
    }

    public function get_pricing_snapshot(): array {
        return $this->pricingsnapshot;
    }

    public function get_pricing_snapshot_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->pricingsnapshot[$key]
            ?? $default;
    }

    public function get_fulfillment_snapshot(): array {
        return $this->fulfillmentsnapshot;
    }

    public function get_fulfillment_snapshot_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->fulfillmentsnapshot[$key]
            ?? $default;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function is_free(): bool {
        return $this->get_net_amount()->is_zero();
    }

    public function has_discount(): bool {
        return !$this->discount->is_zero();
    }

    /**
     * Return a serialisable snapshot suitable for persistence boundaries.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'itemtype' => $this->get_item_type(),
            'itemreference' => $this->get_item_reference(),
            'label' => $this->get_label(),
            'quantity' => $this->quantity,
            'unitprice' => $this->unitprice->to_array(),
            'grossamount' => $this->get_gross_amount()->to_array(),
            'discount' => $this->discount->to_array(),
            'netamount' => $this->get_net_amount()->to_array(),
            'pricingsnapshot' => $this->pricingsnapshot,
            'fulfillmentsnapshot' => $this->fulfillmentsnapshot,
            'metadata' => $this->metadata,
        ];
    }
}
