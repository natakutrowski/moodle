<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\domain\CommerceBundle;
use local_subscriptions\commerce\bundle\domain\CommerceBundleCollection;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Read repository building Bundle aggregates from the generic product catalogue.
 */
final class CommerceBundleRepository {

    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductComponentRepository $components
    ) {
    }

    public function find_by_sku(string $sku): ?CommerceBundle {
        $product = $this->products->find_by_sku($sku);

        if ($product === null || $product->get_type() !== CommerceProductType::BUNDLE) {
            return null;
        }

        return new CommerceBundle(
            $product,
            $this->components->find_by_parent_sku($product->get_sku())
        );
    }

    public function find_all(): CommerceBundleCollection {
        $bundles = [];

        foreach ($this->products->find_by_type(CommerceProductType::BUNDLE) as $product) {
            $bundles[] = new CommerceBundle(
                $product,
                $this->components->find_by_parent_sku($product->get_sku())
            );
        }

        return new CommerceBundleCollection($bundles);
    }
}
