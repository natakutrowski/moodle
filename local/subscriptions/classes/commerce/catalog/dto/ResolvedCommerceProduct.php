<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;

final class ResolvedCommerceProduct {
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly CommerceProductPrice $price,
        private readonly ?CommerceProductTranslation $translation,
        private readonly array $entitlements
    ) {
        foreach ($entitlements as $entitlement) {
            if (!$entitlement instanceof CommerceProductEntitlementDefinition) {
                throw new \coding_exception('Invalid resolved entitlement collection.');
            }
        }
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_price(): CommerceProductPrice {
        return $this->price;
    }

    public function get_translation(): ?CommerceProductTranslation {
        return $this->translation;
    }

    public function get_entitlements(): array {
        return $this->entitlements;
    }

    public function get_display_name(): string {
        return $this->translation?->get_name() ?? $this->product->get_name();
    }
}
