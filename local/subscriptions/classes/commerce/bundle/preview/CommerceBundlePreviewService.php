<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\preview;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Builds a human-readable CRM preview from the recursive expansion engine.
 */
final class CommerceBundlePreviewService {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductEntitlementRepository $entitlements,
        private readonly CommerceBundleExpansionService $expander
    ) {
    }

    public function build(string $sku, bool $allowinactiveroot = false): CommerceBundlePreviewResult {
        $bundle = $this->products->find_by_sku($sku);

        if ($bundle === null || !$bundle->is_bundle()) {
            throw new \coding_exception('A CRM bundle preview requires an existing Bundle product.');
        }

        $expansion = $this->expander->expand($bundle->get_sku(), 1, $allowinactiveroot);
        $items = [];

        foreach ($expansion->get_items() as $expandeditem) {
            $product = $expandeditem->get_product();
            $items[] = new CommerceBundlePreviewItem(
                $product,
                $expandeditem->get_quantity(),
                $expandeditem->get_paths(),
                $this->prices->find_by_product_sku($product->get_sku(), true),
                $this->entitlements->find_by_product_sku($product->get_sku())
            );
        }

        return new CommerceBundlePreviewResult(
            $bundle,
            $items,
            $expansion->get_bundles_visited(),
            $expansion->get_maximum_depth()
        );
    }
}
