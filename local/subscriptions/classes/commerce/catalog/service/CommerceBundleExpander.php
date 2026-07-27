<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\dto\ExpandedCommerceProductItem;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Backward-compatible adapter used by the Native purchase request factory.
 */
final class CommerceBundleExpander {
    private readonly CommerceBundleExpansionService $expansion;

    public function __construct(
        CommerceProductRepository $products,
        CommerceProductComponentRepository $components
    ) {
        $this->expansion = new CommerceBundleExpansionService(
            $products,
            $components
        );
    }

    /**
     * @return ExpandedCommerceProductItem[]
     */
    public function expand(string $sku, int $quantity = 1): array {
        return array_map(
            static fn($item): ExpandedCommerceProductItem => new ExpandedCommerceProductItem(
                $item->get_product(),
                $item->get_quantity()
            ),
            $this->expansion->expand($sku, $quantity)->get_items()
        );
    }
}
