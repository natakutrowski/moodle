<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\expansion;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable result of recursively expanding one Commerce product.
 */
final class CommerceBundleExpansionResult {
    /**
     * @param CommerceBundleExpansionItem[] $items
     */
    public function __construct(
        private readonly string $rootsku,
        private readonly int $rootquantity,
        private readonly array $items,
        private readonly int $bundlesvisited,
        private readonly int $maximumdepth
    ) {
        if ($rootquantity <= 0) {
            throw new \coding_exception('A bundle expansion root quantity must be positive.');
        }

        foreach ($items as $item) {
            if (!$item instanceof CommerceBundleExpansionItem) {
                throw new \coding_exception('Invalid bundle expansion item collection.');
            }
        }
    }

    public function get_root_sku(): string {
        return $this->rootsku;
    }

    public function get_root_quantity(): int {
        return $this->rootquantity;
    }

    /**
     * @return CommerceBundleExpansionItem[]
     */
    public function get_items(): array {
        return $this->items;
    }

    public function get_item_count(): int {
        return count($this->items);
    }

    public function get_total_quantity(): int {
        return array_sum(array_map(
            static fn(CommerceBundleExpansionItem $item): int => $item->get_quantity(),
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
            'rootsku' => $this->rootsku,
            'rootquantity' => $this->rootquantity,
            'items' => array_map(
                static fn(CommerceBundleExpansionItem $item): array => $item->to_array(),
                $this->items
            ),
            'itemcount' => $this->get_item_count(),
            'totalquantity' => $this->get_total_quantity(),
            'bundlesvisited' => $this->bundlesvisited,
            'maximumdepth' => $this->maximumdepth,
        ];
    }
}
