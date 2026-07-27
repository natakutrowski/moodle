<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;

/** Converts visual editor rows into canonical bundle components. */
final class CommerceBundleComponentInput {
    /**
     * @param array<int, array{sku:mixed, quantity:mixed, sortorder?:mixed}> $rows
     * @return CommerceProductComponent[]
     */
    public function build(string $parentsku, array $rows): array {
        $components = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $childsku = strtoupper(trim((string) ($row['sku'] ?? '')));

            if ($childsku === '') {
                continue;
            }

            if (isset($seen[$childsku])) {
                throw new \coding_exception('A bundle component can only be selected once: ' . $childsku);
            }

            $seen[$childsku] = true;
            $quantity = (int) ($row['quantity'] ?? 1);
            $sortorder = isset($row['sortorder']) ? (int) $row['sortorder'] : $index;
            $components[] = new CommerceProductComponent(
                $parentsku,
                $childsku,
                $quantity,
                $sortorder
            );
        }

        if ($components === []) {
            throw new \coding_exception('A Commerce bundle must contain at least one component.');
        }

        usort(
            $components,
            static fn(CommerceProductComponent $left, CommerceProductComponent $right): int =>
                [$left->get_sort_order(), $left->get_child_product_sku()]
                <=> [$right->get_sort_order(), $right->get_child_product_sku()]
        );

        return $components;
    }
}
