<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\preview;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * Rich CRM preview for one Bundle product.
 */
final class CommerceBundlePreviewResult {
    /** @param CommerceBundlePreviewItem[] $items */
    public function __construct(
        private readonly CommerceProduct $bundle,
        private readonly array $items,
        private readonly int $bundlesvisited,
        private readonly int $maximumdepth
    ) {
        foreach ($items as $item) {
            if (!$item instanceof CommerceBundlePreviewItem) {
                throw new \coding_exception('Invalid bundle preview item collection.');
            }
        }
    }

    public function get_bundle(): CommerceProduct {
        return $this->bundle;
    }

    /** @return CommerceBundlePreviewItem[] */
    public function get_items(): array {
        return $this->items;
    }

    public function get_product_count(): int {
        return count($this->items);
    }

    public function get_total_quantity(): int {
        return array_sum(array_map(
            static fn(CommerceBundlePreviewItem $item): int => $item->get_quantity(),
            $this->items
        ));
    }

    public function get_entitlement_count(): int {
        return array_sum(array_map(
            static fn(CommerceBundlePreviewItem $item): int => count($item->get_entitlements()),
            $this->items
        ));
    }

    public function get_price_count(): int {
        return array_sum(array_map(
            static fn(CommerceBundlePreviewItem $item): int => count($item->get_prices()),
            $this->items
        ));
    }

    public function get_bundles_visited(): int {
        return $this->bundlesvisited;
    }

    public function get_maximum_depth(): int {
        return $this->maximumdepth;
    }

    public function to_array(): array {
        return [
            'bundle' => $this->bundle->to_array(),
            'items' => array_map(
                static fn(CommerceBundlePreviewItem $item): array => $item->to_array(),
                $this->items
            ),
            'productcount' => $this->get_product_count(),
            'totalquantity' => $this->get_total_quantity(),
            'entitlementcount' => $this->get_entitlement_count(),
            'pricecount' => $this->get_price_count(),
            'bundlesvisited' => $this->bundlesvisited,
            'maximumdepth' => $this->maximumdepth,
        ];
    }
}
