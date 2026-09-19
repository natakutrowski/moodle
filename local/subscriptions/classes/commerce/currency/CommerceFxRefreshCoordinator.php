<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
defined('MOODLE_INTERNAL') || die();
/** Free on-demand chain: ECB first, Frankfurter only for unresolved currencies. */
final class CommerceFxRefreshCoordinator {
    public function __construct(
        private readonly CommerceFxRateSourceInterface $primary = new CommerceEcbFxRateSource(),
        private readonly CommerceFxRateSourceInterface $secondary = new CommerceFrankfurterFxRateSource()
    ) {}
    /** @param string[] $targetcurrencies */
    public function refresh(string $basecurrency, array $targetcurrencies): CommerceFxRefreshBatch {
        $primary = $this->primary->refresh($basecurrency, $targetcurrencies);
        $entries = [];
        foreach ($primary->rates as $code => $rate) {
            $entries[$code] = ['rate'=>(string)$rate, 'source'=>$primary->source, 'referencedate'=>$primary->referencedate];
        }
        $remaining = $primary->unavailable;
        if ($remaining !== []) {
            try {
                $secondary = $this->secondary->refresh($basecurrency, $remaining);
                foreach ($secondary->rates as $code => $rate) {
                    $entries[$code] = ['rate'=>(string)$rate, 'source'=>$secondary->source, 'referencedate'=>$secondary->referencedate];
                }
                $remaining = $secondary->unavailable;
            } catch (\Throwable $e) {
                // Keep valid ECB results if the secondary free service is unavailable.
            }
        }
        return new CommerceFxRefreshBatch($basecurrency, $entries, array_values(array_unique($remaining)));
    }
}
