<?php

namespace local_subscriptions\commerce\catalog\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\dto\ResolvedCommerceProduct;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

/**
 * Converts one resolved Native catalogue product into a locked purchase item.
 */
final class CommerceCatalogPurchaseItemFactory {

    public function create(
        ResolvedCommerceProduct $resolved,
        int $quantity = 1
    ): CommercePurchaseRequestItem {
        if ($quantity <= 0) {
            throw new \coding_exception('A catalogue purchase item quantity must be positive.');
        }

        $product = $resolved->get_product();
        $price = $resolved->get_price();
        $legacyid = $this->resolve_legacy_id($product->get_metadata());
        $itemtype = $this->map_item_type($product->get_type());

        $item = new CommerceItem(
            $itemtype,
            $product->get_sku(),
            $resolved->get_display_name(),
            $legacyid,
            [
                'catalogue_product_id' => $product->get_id(),
                'catalogue_sku' => $product->get_sku(),
                'catalogue_type' => $product->get_type(),
                'catalogue_metadata' => $product->get_metadata(),
            ]
        );

        return new CommercePurchaseRequestItem(
            $item,
            $quantity,
            $price->get_amount_minor(),
            $price->get_currency(),
            [
                'catalogue_product_id' => $product->get_id(),
                'catalogue_sku' => $product->get_sku(),
                'provider' => $price->get_provider(),
                'provider_price_id' => $price->get_provider_price_id(),
                'entitlements' => array_map(
                    static fn($definition): array => $definition->to_array(),
                    $resolved->get_entitlements()
                ),
                'legacyfamily' => $product->get_metadata_value('legacyfamily'),
                'legacyid' => $legacyid,
            ]
        );
    }

    private function map_item_type(string $producttype): string {
        return match ($producttype) {
            CommerceProductType::COURSE_ACCESS => CommerceItem::TYPE_SUBSCRIPTION,
            CommerceProductType::DIGITAL_DOWNLOAD => CommerceItem::TYPE_DIGITAL,
            CommerceProductType::BUNDLE => CommerceItem::TYPE_BUNDLE,
            CommerceProductType::SERVICE => CommerceItem::TYPE_SERVICE,
            default => throw new \coding_exception(
                'Unsupported Native catalogue product type for purchase preparation: ' . $producttype
            ),
        };
    }

    private function resolve_legacy_id(array $metadata): ?int {
        $legacyid = (int)($metadata['legacyid'] ?? 0);
        return $legacyid > 0 ? $legacyid : null;
    }
}
