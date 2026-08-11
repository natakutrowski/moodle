<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\expansion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Recursively flattens composed Commerce products into purchasable leaf items.
 */
final class CommerceBundleExpansionService {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductComponentRepository $components
    ) {
    }

    public function expand(
        string $sku,
        int $quantity = 1,
        bool $allowinactiveroot = false
    ): CommerceBundleExpansionResult {
        $sku = strtoupper(trim($sku));

        if ($quantity <= 0) {
            throw new \coding_exception('Bundle expansion quantity must be positive.');
        }

        $items = [];
        $visitedbundles = [];
        $maximumdepth = 0;

        $this->walk(
            $sku,
            $quantity,
            [],
            [],
            $items,
            $visitedbundles,
            $maximumdepth,
            $allowinactiveroot
        );

        ksort($items);

        return new CommerceBundleExpansionResult(
            $sku,
            $quantity,
            array_values($items),
            count($visitedbundles),
            $maximumdepth
        );
    }

    private function walk(
        string $sku,
        int $quantity,
        array $ancestors,
        array $path,
        array &$items,
        array &$visitedbundles,
        int &$maximumdepth,
        bool $allowinactiveroot
    ): void {
        if (isset($ancestors[$sku])) {
            $cycle = array_merge($path, [$sku]);

            throw new \coding_exception(
                'A cycle was detected in Commerce bundle composition: ' . implode(' -> ', $cycle)
            );
        }

        $product = $this->products->find_by_sku($sku);

        if ($product === null) {
            throw new \coding_exception('Unknown Commerce product in bundle composition: ' . $sku);
        }

        $isroot = $path === [];

        if (!$product->is_active() && !($isroot && $allowinactiveroot)) {
            throw new \coding_exception('Inactive Commerce product in bundle composition: ' . $sku);
        }

        $path[] = $sku;
        $maximumdepth = max($maximumdepth, count($path) - 1);

        if (!$product->is_bundle()) {
            if (isset($items[$sku])) {
                $items[$sku] = $items[$sku]->with_added_quantity($quantity, $path);
            } else {
                $items[$sku] = new CommerceBundleExpansionItem(
                    $product,
                    $quantity,
                    [$path]
                );
            }

            return;
        }

        $children = $this->components->find_by_parent_sku($sku);

        if ($children === []) {
            throw new \coding_exception('A Commerce bundle must contain at least one component: ' . $sku);
        }

        $visitedbundles[$sku] = true;
        $ancestors[$sku] = true;

        foreach ($children as $component) {
            $this->walk(
                $component->get_child_product_sku(),
                $quantity * $component->get_quantity(),
                $ancestors,
                $path,
                $items,
                $visitedbundles,
                $maximumdepth,
                $allowinactiveroot
            );
        }
    }
}
