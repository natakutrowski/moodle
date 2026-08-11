<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\dto\ResolvedCommerceProduct;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

final class CommerceProductResolver {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations,
        private readonly CommerceProductEntitlementRepository $entitlements,
        private readonly ?CommerceEffectiveEntitlementResolver $effectiveentitlements = null
    ) {
    }

    public function resolve_for_purchase(
        string $sku,
        string $currency,
        string $language,
        ?string $provider = null,
        ?int $at = null
    ): ResolvedCommerceProduct {
        $product = $this->products->find_by_sku($sku);

        if (!$product) {
            throw new \coding_exception('Unknown Commerce product SKU.');
        }

        if (!$product->is_available_at($at ?? time())) {
            throw new \coding_exception('Commerce product is not currently available.');
        }

        $price = $this->prices->find_active($product->get_sku(), $currency, $provider);

        if (!$price) {
            throw new \coding_exception(
                'No active Commerce price matches the requested currency and provider.'
            );
        }

        return new ResolvedCommerceProduct(
            $product,
            $price,
            $this->translations->find($product->get_sku(), $language),
            $this->effectiveentitlements !== null
                ? $this->effectiveentitlements->resolve($product)
                : $this->entitlements->find_by_product_sku($product->get_sku())
        );
    }
}
