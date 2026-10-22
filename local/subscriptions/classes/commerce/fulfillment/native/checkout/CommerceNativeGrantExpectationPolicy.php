<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

/**
 * Decides whether Native Commerce products are expected to produce entitlement grants.
 *
 * A product may explicitly opt out through its registered product-type capabilities.
 * Expandable product types such as bundles remain grant-producing at purchase level,
 * because their leaf products determine the actual grant plan.
 */
final class CommerceNativeGrantExpectationPolicy {
    private readonly CommerceProductRepository $products;
    private readonly CommerceProductPriceRepository $prices;
    private readonly CommerceProductTypeRegistry $types;

    public function __construct(private readonly \moodle_database $db) {
        $hydrator = new CommerceCatalogHydrator();
        $this->products = new CommerceProductRepository($db, $hydrator);
        $this->prices = new CommerceProductPriceRepository($db, $hydrator, $this->products);
        $this->types = CommerceProductTypeRegistry::create_default();
    }

    public function product_requires_grants(CommerceProduct $product): bool {
        $type = $product->get_type();
        if (!$this->types->has($type)) {
            throw new \RuntimeException('Unknown Commerce product type in Native grant policy: ' . $type . '.');
        }

        $capabilities = $this->types->get($type)->get_capabilities();

        return $capabilities->supports_entitlements()
            || $capabilities->is_expandable();
    }

    /** @param \stdClass[] $items */
    public function allows_empty_grant_plan(array $items): bool {
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            $sku = $this->resolve_product_sku($item);
            $product = $this->products->find_by_sku($sku);
            if ($product === null || $this->product_requires_grants($product)) {
                return false;
            }
        }

        return true;
    }

    private function resolve_product_sku(\stdClass $item): string {
        $priceid = $this->extract_price_id($item);
        if ($priceid > 0) {
            $price = $this->prices->find_by_id($priceid);
            if ($price !== null) {
                return $price->get_product_sku();
            }
        }

        return strtoupper(trim((string)($item->itemreference ?? '')));
    }

    private function extract_price_id(\stdClass $item): int {
        foreach (['fulfillmentjson', 'metadatajson'] as $field) {
            $decoded = json_decode((string)($item->{$field} ?? ''), true);
            if (is_array($decoded) && !empty($decoded['priceid'])) {
                return (int)$decoded['priceid'];
            }
        }

        return 0;
    }
}
