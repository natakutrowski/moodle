<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\preview;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * One terminal product displayed in a CRM bundle preview.
 */
final class CommerceBundlePreviewItem {
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly int $quantity,
        private readonly array $paths,
        private readonly array $prices,
        private readonly array $entitlements
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception('A bundle preview item quantity must be positive.');
        }
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_paths(): array {
        return $this->paths;
    }

    public function get_prices(): array {
        return $this->prices;
    }

    public function get_entitlements(): array {
        return $this->entitlements;
    }

    public function to_array(): array {
        return [
            'product' => $this->product->to_array(),
            'quantity' => $this->quantity,
            'paths' => $this->paths,
            'prices' => array_map(static fn($price): array => $price->to_array(), $this->prices),
            'entitlements' => array_map(static fn($entitlement): array => $entitlement->to_array(), $this->entitlements),
        ];
    }
}
