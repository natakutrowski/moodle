<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\expansion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * One flattened purchasable item produced by bundle expansion.
 */
final class CommerceBundleExpansionItem {
    /**
     * @param string[][] $paths
     */
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly int $quantity,
        private readonly array $paths = []
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception('A bundle expansion item quantity must be positive.');
        }
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_sku(): string {
        return $this->product->get_sku();
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    /**
     * @return string[][]
     */
    public function get_paths(): array {
        return $this->paths;
    }

    public function with_added_quantity(int $quantity, array $path): self {
        if ($quantity <= 0) {
            throw new \coding_exception('An added bundle expansion quantity must be positive.');
        }

        $paths = $this->paths;
        $paths[] = array_values($path);

        return new self(
            $this->product,
            $this->quantity + $quantity,
            $paths
        );
    }

    public function to_array(): array {
        return [
            'sku' => $this->get_sku(),
            'type' => $this->product->get_type(),
            'quantity' => $this->quantity,
            'paths' => $this->paths,
        ];
    }
}
