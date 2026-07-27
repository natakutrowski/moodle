<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionResult;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/**
 * Complete backend payload for a CRM product editor.
 */
final class CommerceCatalogProductEditorData {
    public function __construct(
        private readonly CommerceProduct $product,
        private readonly array $prices,
        private readonly array $translations,
        private readonly array $components,
        private readonly array $entitlements,
        private readonly ?CommerceBundleExpansionResult $expansion
    ) {
    }

    public function get_product(): CommerceProduct {
        return $this->product;
    }

    public function get_prices(): array {
        return $this->prices;
    }

    public function get_translations(): array {
        return $this->translations;
    }

    public function get_components(): array {
        return $this->components;
    }

    public function get_entitlements(): array {
        return $this->entitlements;
    }

    public function get_expansion(): ?CommerceBundleExpansionResult {
        return $this->expansion;
    }

    public function to_array(): array {
        return [
            'product' => $this->product->to_array(),
            'prices' => array_map(
                static fn($price): array => $price->to_array(),
                $this->prices
            ),
            'translations' => array_map(
                static fn($translation): array => $translation->to_array(),
                $this->translations
            ),
            'components' => array_map(
                static fn($component): array => $component->to_array(),
                $this->components
            ),
            'entitlements' => array_map(
                static fn($entitlement): array => $entitlement->to_array(),
                $this->entitlements
            ),
            'expansion' => $this->expansion?->to_array(),
        ];
    }
}
