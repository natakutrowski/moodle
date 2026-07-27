<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * Read model used by the future unified CRM Commerce product list.
 */
final class CommerceCatalogProductSummary {
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly int $pricecount,
        private readonly int $translationcount,
        private readonly int $componentcount,
        private readonly int $entitlementcount
    ) {
        foreach ([$pricecount, $translationcount, $componentcount, $entitlementcount] as $count) {
            if ($count < 0) {
                throw new \coding_exception('Commerce product summary counts cannot be negative.');
            }
        }
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_sku(): string {
        return $this->product->get_sku();
    }

    public function get_price_count(): int {
        return $this->pricecount;
    }

    public function get_translation_count(): int {
        return $this->translationcount;
    }

    public function get_component_count(): int {
        return $this->componentcount;
    }

    public function get_entitlement_count(): int {
        return $this->entitlementcount;
    }

    public function to_array(): array {
        return [
            'id' => $this->product->get_id(),
            'sku' => $this->product->get_sku(),
            'type' => $this->product->get_type(),
            'status' => $this->product->get_status(),
            'name' => $this->product->get_name(),
            'description' => $this->product->get_description(),
            'pricecount' => $this->pricecount,
            'translationcount' => $this->translationcount,
            'componentcount' => $this->componentcount,
            'entitlementcount' => $this->entitlementcount,
            'timemodified' => $this->product->get_time_modified(),
        ];
    }
}
