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

    /**
     * Returns currencies in which a product can actually be priced.
     *
     * For a Bundle, component-derived currencies are an intersection, not a
     * union: a calculated price can only exist when every terminal component
     * has an active price in the same currency. Direct Bundle prices are kept
     * as additional currencies for the fixed-price strategy.
     *
     * @return string[]
     */
    public function get_product_currencies(
        string $sku,
        bool $includecomponents = true,
        bool $allowinactiveroot = false
    ): array {
        $directcurrencies = [];
        foreach ($this->prices->find_by_product_sku($sku, true) as $price) {
            $directcurrencies[$price->get_currency()] = true;
        }

        if (!$includecomponents) {
            $result = array_keys($directcurrencies);
            sort($result, SORT_STRING);
            return $result;
        }

        $commoncomponentcurrencies = null;
        try {
            foreach ($this->preview->build($sku, $allowinactiveroot)->get_items() as $item) {
                $itemcurrencies = [];
                foreach ($item->get_prices() as $price) {
                    if ($price->is_active()) {
                        $itemcurrencies[$price->get_currency()] = true;
                    }
                }

                $commoncomponentcurrencies = $commoncomponentcurrencies === null
                    ? $itemcurrencies
                    : array_intersect_key($commoncomponentcurrencies, $itemcurrencies);
            }
        } catch (\Throwable) {
            // Draft products can legitimately have no valid composition yet.
        }

        $currencies = $directcurrencies;
        foreach (array_keys($commoncomponentcurrencies ?? []) as $currency) {
            $currencies[$currency] = true;
        }

        $result = array_keys($currencies);
        sort($result, SORT_STRING);
        return $result;
    }
}
