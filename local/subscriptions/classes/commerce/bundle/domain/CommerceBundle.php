<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/**
 * Aggregate representing a Commerce bundle and its direct components.
 */
final class CommerceBundle {

    /** @var CommerceProductComponent[] */
    private array $components;

    /**
     * @param CommerceProductComponent[] $components
     */
    public function __construct(
        private readonly CommerceProduct $product,
        array $components
    ) {
        if ($product->get_type() !== CommerceProductType::BUNDLE) {
            throw new \coding_exception('A Commerce bundle aggregate requires a bundle product.');
        }

        $seen = [];

        foreach ($components as $component) {
            if (!$component instanceof CommerceProductComponent) {
                throw new \coding_exception('A Commerce bundle component collection is invalid.');
            }

            if ($component->get_parent_product_sku() !== $product->get_sku()) {
                throw new \coding_exception('A Commerce bundle component belongs to another parent product.');
            }

            $childsku = $component->get_child_product_sku();

            if (isset($seen[$childsku])) {
                throw new \coding_exception('A Commerce bundle cannot contain the same direct component twice.');
            }

            $seen[$childsku] = true;
        }

        usort(
            $components,
            static fn(CommerceProductComponent $left, CommerceProductComponent $right): int =>
                [$left->get_sort_order(), $left->get_child_product_sku()]
                <=> [$right->get_sort_order(), $right->get_child_product_sku()]
        );

        $this->components = array_values($components);
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_sku(): string {
        return $this->product->get_sku();
    }

    public function get_name(): string {
        return $this->product->get_name();
    }

    public function is_active(): bool {
        return $this->product->is_active();
    }

    /**
     * @return CommerceProductComponent[]
     */
    public function get_components(): array {
        return $this->components;
    }

    public function get_component_count(): int {
        return count($this->components);
    }

    public function is_empty(): bool {
        return $this->components === [];
    }

    public function to_array(): array {
        return [
            'product' => $this->product->to_array(),
            'components' => array_map(
                static fn(CommerceProductComponent $component): array => $component->to_array(),
                $this->components
            ),
        ];
    }
}
