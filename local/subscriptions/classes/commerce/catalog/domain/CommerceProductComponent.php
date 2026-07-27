<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable relation between a bundle and one of its child products.
 */
final class CommerceProductComponent {

    private readonly string $parentproductsku;
    private readonly string $childproductsku;

    public function __construct(
        string $parentproductsku,
        string $childproductsku,
        private readonly int $quantity = 1,
        private readonly int $sortorder = 0,
        private readonly array $metadata = [],
        private readonly ?int $id = null
    ) {
        $parentproductsku = strtoupper(trim($parentproductsku));
        $childproductsku = strtoupper(trim($childproductsku));

        if ($parentproductsku === '' || $childproductsku === '') {
            throw new \coding_exception('A Commerce product component requires parent and child SKUs.');
        }

        if ($parentproductsku === $childproductsku) {
            throw new \coding_exception('A Commerce bundle cannot contain itself.');
        }

        if ($quantity <= 0) {
            throw new \coding_exception('A Commerce product component quantity must be positive.');
        }

        if ($sortorder < 0) {
            throw new \coding_exception('A Commerce product component sort order cannot be negative.');
        }

        if ($id !== null && $id <= 0) {
            throw new \coding_exception('A Commerce product component identifier must be positive.');
        }

        $this->parentproductsku = $parentproductsku;
        $this->childproductsku = $childproductsku;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_parent_product_sku(): string {
        return $this->parentproductsku;
    }

    public function get_child_product_sku(): string {
        return $this->childproductsku;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_sort_order(): int {
        return $this->sortorder;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'parentproductsku' => $this->parentproductsku,
            'childproductsku' => $this->childproductsku,
            'quantity' => $this->quantity,
            'sortorder' => $this->sortorder,
            'metadata' => $this->metadata,
        ];
    }
}
