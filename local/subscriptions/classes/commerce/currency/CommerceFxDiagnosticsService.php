<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductManager;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only closure diagnostics for Commerce Currency & FX.
 *
 * This service never fetches a rate, never writes configuration and never
 * creates a catalogue price.
 */
final class CommerceFxDiagnosticsService {
    public const DEFAULT_STALE_DAYS = 7;

    public function __construct(
        private readonly CommerceCurrencyRegistry $currencies,
        private readonly CommerceFxRateBook $ratebook,
        private readonly CommerceCatalogProductManager $products
    ) {
    }

    /**
     * @return array{
     *   basecurrency:string,
     *   enabled:string[],
     *   ratecount:int,
     *   missingrates:string[],
     *   stalerates:string[],
     *   sourcecounts:array<string,int>,
     *   activeproducts:int,
     *   productsmissingbase:array<int,array{sku:string,name:string}>,
     *   automaticrefresh:bool,
     *   healthy:bool
     * }
     */
    public function snapshot(?int $now = null, int $staledays = self::DEFAULT_STALE_DAYS): array {
        $now ??= time();
        $staledays = max(1, $staledays);

        $enabled = $this->currencies->enabled();
        $base = $this->ratebook->base_currency();
        $rates = $this->ratebook->rates();

        $missing = [];
        $stale = [];
        $sources = [];
        $staleafter = $now - ($staledays * DAYSECS);

        foreach ($enabled as $currency) {
            if ($currency === $base) {
                continue;
            }

            $entry = $rates[$currency] ?? null;
            if ($entry === null) {
                $missing[] = $currency;
                continue;
            }

            $source = trim((string)($entry['source'] ?? '')) ?: 'manual';
            $sources[$source] = ($sources[$source] ?? 0) + 1;

            $updatedat = (int)($entry['updatedat'] ?? 0);
            if ($updatedat <= 0 || $updatedat < $staleafter) {
                $stale[] = $currency;
            }
        }

        ksort($sources, SORT_NATURAL | SORT_FLAG_CASE);

        $activeproducts = 0;
        $missingbase = [];

        foreach ($this->products->list_products() as $summary) {
            $product = $summary->get_product();
            if (!$product->is_active()) {
                continue;
            }

            $activeproducts++;
            $hasbase = false;

            foreach ($this->products->get_editor_data($product->get_sku())->get_prices() as $price) {
                if (
                    $price->get_provider() === null
                    && $price->is_active()
                    && Currency::sanitize($price->get_currency()) === $base
                ) {
                    $hasbase = true;
                    break;
                }
            }

            if (!$hasbase) {
                $missingbase[] = [
                    'sku' => $product->get_sku(),
                    'name' => $product->get_name(),
                ];
            }
        }

        return [
            'basecurrency' => $base,
            'enabled' => $enabled,
            'ratecount' => count($rates),
            'missingrates' => $missing,
            'stalerates' => $stale,
            'sourcecounts' => $sources,
            'activeproducts' => $activeproducts,
            'productsmissingbase' => $missingbase,
            // D2/D3 deliberately define refresh as explicit admin action only.
            'automaticrefresh' => false,
            'healthy' => $missing === [] && $missingbase === [],
        ];
    }
}
