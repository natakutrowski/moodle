<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductManager;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Catalogue-wide international price suggestions.
 *
 * Bundles are deliberately excluded: their pricing rules are handled by D5.
 */
final class CommerceCatalogFxBulkSuggestionService {
    public function __construct(
        private readonly CommerceCatalogProductManager $products,
        private readonly CommerceFxRateBook $ratebook,
        private readonly CommerceFxPriceSuggestionService $suggestions
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function preview(string $targetcurrency, string $rounding = 'none'): array {
        $targetcurrency = Currency::sanitize($targetcurrency);
        if ($targetcurrency === '') {
            throw new \coding_exception('Invalid target currency.');
        }

        $basecurrency = $this->ratebook->base_currency();
        if ($targetcurrency === $basecurrency) {
            return [];
        }

        if ($this->ratebook->rate_for($targetcurrency) === null) {
            throw new \coding_exception(
                'No FX rate is configured for ' . $targetcurrency . '.'
            );
        }

        $bookrates = $this->ratebook->rates();
        $ratemetadata = $bookrates[$targetcurrency] ?? [];
        $rows = [];

        foreach ($this->products->list_products() as $summary) {
            $product = $summary->get_product();

            // Bundle pricing has dedicated semantics and its own D5 assistant.
            if ($product->is_bundle()) {
                continue;
            }

            $editor = $this->products->get_editor_data($product->get_sku());
            $sourceprice = null;
            $hastarget = false;

            foreach ($editor->get_prices() as $price) {
                if ($price->get_provider() !== null) {
                    continue;
                }

                if ($price->get_currency() === $targetcurrency) {
                    $hastarget = true;
                    break;
                }

                if (
                    $price->is_active()
                    && $price->get_currency() === $basecurrency
                ) {
                    $sourceprice = $price;
                }
            }

            if ($hastarget || $sourceprice === null) {
                continue;
            }

            $suggestion = $this->suggestions->suggest(
                $sourceprice->get_amount_minor(),
                $basecurrency,
                $targetcurrency,
                $rounding
            );

            $rows[] = [
                'productid' => (int)$product->get_id(),
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'sourcecurrency' => $basecurrency,
                'sourceamountminor' => $sourceprice->get_amount_minor(),
                'targetcurrency' => $targetcurrency,
                'rawmajor' => $suggestion['rawmajor'],
                'suggestedmajor' => $suggestion['suggestedmajor'],
                'suggestedminor' => $suggestion['suggestedminor'],
                'rate' => $suggestion['rate'],
                'source' => trim((string)($ratemetadata['source'] ?? 'manual')),
                'updatedat' => (int)($ratemetadata['updatedat'] ?? 0),
            ];
        }

        usort(
            $rows,
            static fn(array $a, array $b): int =>
                strnatcasecmp(
                    (string)$a['name'] . ' ' . (string)$a['sku'],
                    (string)$b['name'] . ' ' . (string)$b['sku']
                )
        );

        return $rows;
    }
}
