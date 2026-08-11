<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\domain;

defined('MOODLE_INTERNAL') || die();

/** Lightweight immutable cart line containing only stable catalogue references. */
final class CommerceCartItem {
    private readonly string $productsku;

    public function __construct(
        string $productsku,
        private readonly int $priceid,
        private readonly int $quantity = 1,
        private readonly array $metadata = []
    ) {
        $productsku = strtoupper(trim($productsku));

        if ($productsku === '') {
            throw new \coding_exception('A Commerce cart item requires a product SKU.');
        }

        if ($priceid <= 0) {
            throw new \coding_exception('A Commerce cart item requires a positive price identifier.');
        }

        if ($quantity <= 0) {
            throw new \coding_exception('A Commerce cart item quantity must be positive.');
        }

        $this->productsku = $productsku;
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_price_id(): int {
        return $this->priceid;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_key(): string {
        return $this->productsku . ':' . $this->priceid;
    }

    public function with_quantity(int $quantity): self {
        return new self($this->productsku, $this->priceid, $quantity, $this->metadata);
    }

    public function to_array(): array {
        return [
            'productsku' => $this->productsku,
            'priceid' => $this->priceid,
            'quantity' => $this->quantity,
            'metadata' => $this->metadata,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            (string)($data['productsku'] ?? ''),
            (int)($data['priceid'] ?? 0),
            (int)($data['quantity'] ?? 0),
            (array)($data['metadata'] ?? [])
        );
    }
}
