<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds non-authoritative international price suggestions for a product.
 *
 * Existing target-currency prices are intentionally excluded: D4 never
 * silently replaces a price already chosen by an administrator.
 */
final class CommerceProductFxSuggestionService {
    public function __construct(
        private readonly CommerceFxRateBook $ratebook,
        private readonly CommerceFxPriceSuggestionService $suggestions
    ) {
    }

    /**
     * @param CommerceProductPrice[] $prices
     * @param string[] $enabledcurrencies
     * @return array{
     *     sourcecurrency:string,
     *     sourceamountminor:int,
     *     suggestions:array<string,array{
     *         currency:string,
     *         rawmajor:float,
     *         suggestedmajor:float,
     *         suggestedminor:int,
     *         rate:string,
     *         source:string,
     *         updatedat:int
     *     }>,
     *     missingrates:string[]
     * }
     */
    public function suggest_missing(
        array $prices,
        array $enabledcurrencies,
        string $rounding = 'none'
    ): array {
        $base = $this->ratebook->base_currency();
        $source = null;
        $existing = [];

        foreach ($prices as $price) {
            if (!$price instanceof CommerceProductPrice) {
                throw new \coding_exception('Invalid Commerce product price collection.');
            }
            $currency = Currency::sanitize($price->get_currency());
            $existing[$currency] = true;
            if ($currency === $base) {
                $source = $price;
            }
        }

        if ($source === null) {
            throw new \coding_exception('The product has no price in the configured FX base currency.');
        }

        $bookrates = $this->ratebook->rates();
        $items = [];
        $missingrates = [];

        foreach ($enabledcurrencies as $rawcurrency) {
            $currency = Currency::sanitize((string)$rawcurrency);
            if (
                $currency === ''
                || $currency === $base
                || isset($existing[$currency])
            ) {
                continue;
            }

            if ($this->ratebook->rate_for($currency) === null) {
                $missingrates[] = $currency;
                continue;
            }

            $suggestion = $this->suggestions->suggest(
                $source->get_amount_minor(),
                $base,
                $currency,
                $rounding
            );
            $metadata = $bookrates[$currency] ?? [];

            $items[$currency] = [
                'currency' => $currency,
                'rawmajor' => $suggestion['rawmajor'],
                'suggestedmajor' => $suggestion['suggestedmajor'],
                'suggestedminor' => $suggestion['suggestedminor'],
                'rate' => $suggestion['rate'],
                'source' => trim((string)($metadata['source'] ?? 'manual')),
                'updatedat' => (int)($metadata['updatedat'] ?? 0),
            ];
        }

        return [
            'sourcecurrency' => $base,
            'sourceamountminor' => $source->get_amount_minor(),
            'suggestions' => $items,
            'missingrates' => array_values(array_unique($missingrates)),
        ];
    }
}
