<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductManager;
use local_subscriptions\commerce\catalog\service\CommerceCatalogCurrencyService;
use local_subscriptions\commerce\catalog\service\CommerceCatalogLocaleService;

/** Certifies dynamic language/currency discovery for the CRM catalogue. */
final class CommerceCatalogInternationalisationAuditor {
    public function __construct(
        private readonly CommerceCatalogProductManager $products,
        private readonly CommerceCatalogLocaleService $locales,
        private readonly CommerceCatalogCurrencyService $currencies
    ) {
    }

    public function audit(): array {
        $languages = $this->locales->get_languages();
        $currencyset = [];
        $bundles = 0;
        $errors = [];

        foreach ($this->products->list_products() as $summary) {
            $product = $summary->get_product();
            if (!$product->is_bundle()) {
                continue;
            }
            $bundles++;
            foreach ($this->currencies->get_product_currencies($product->get_sku()) as $currency) {
                $currencyset[$currency] = true;
            }
        }

        if ($languages === []) {
            $errors[] = 'No editable Moodle language was discovered.';
        }

        return [
            'languages' => count($languages),
            'languagecodes' => array_keys($languages),
            'bundles' => $bundles,
            'currencies' => count($currencyset),
            'currencycodes' => array_keys($currencyset),
            'errors' => $errors,
            'certified' => $errors === [],
        ];
    }
}
