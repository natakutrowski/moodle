<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

final class CommerceCatalogReadService {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations
    ) {
    }

    public function find_by_sku(string $sku) {
        return $this->products->find_by_sku($sku);
    }

    public function find_translation(string $sku, string $language) {
        return $this->translations->find($sku, $language);
    }

    public function get_available_prices(string $sku): array {
        return $this->prices->find_by_product_sku($sku, true);
    }

    public function find_active_products(): array {
        return $this->products->find_active();
    }
}
