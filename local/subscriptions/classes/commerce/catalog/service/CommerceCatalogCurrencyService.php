<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewService;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;

/** Resolves currencies from catalogue data instead of a hard-coded market list. */
final class CommerceCatalogCurrencyService {
    public function __construct(
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceBundlePreviewService $preview
    ) {
    }

    /** @return string[] */
    public function get_product_currencies(string $sku, bool $includecomponents = true): array {
        $currencies = [];
        foreach ($this->prices->find_by_product_sku($sku) as $price) {
            $currencies[$price->get_currency()] = true;
        }

        if ($includecomponents) {
            try {
                foreach ($this->preview->build($sku)->get_items() as $item) {
                    foreach ($item->get_prices() as $price) {
                        $currencies[$price->get_currency()] = true;
                    }
                }
            } catch (\Throwable) {
                // Draft products can legitimately have no valid composition yet.
            }
        }

        $result = array_keys($currencies);
        sort($result, SORT_STRING);
        return $result;
    }
}
