<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency;

use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/** Produces non-authoritative price suggestions from an administrative FX rate. */
final class CommerceFxPriceSuggestionService {
    public function __construct(private readonly CommerceFxRateBook $rates) {
    }

    /**
     * @return array{rawmajor:float,suggestedmajor:float,suggestedminor:int,rate:string}
     */
    public function suggest(int $sourceamountminor, string $sourcecurrency, string $targetcurrency, string $rounding = 'none'): array {
        $sourcecurrency = Currency::sanitize($sourcecurrency);
        $targetcurrency = Currency::sanitize($targetcurrency);
        if ($sourcecurrency === '' || $targetcurrency === '') {
            throw new \coding_exception('Invalid FX suggestion currency.');
        }
        if ($sourcecurrency !== $this->rates->base_currency()) {
            throw new \coding_exception('FX suggestions must start from the configured base currency.');
        }
        $rate = $this->rates->rate_for($targetcurrency);
        if ($rate === null) {
            throw new \coding_exception('Missing FX rate for ' . $targetcurrency . '.');
        }
        $sourcefactor = 10 ** Currency::minor_unit_exponent($sourcecurrency);
        $rawmajor = ($sourceamountminor / $sourcefactor) * (float)$rate;
        $suggestedmajor = $this->apply_rounding($rawmajor, $targetcurrency, $rounding);
        $targetfactor = 10 ** Currency::minor_unit_exponent($targetcurrency);
        $suggestedminor = (int)round($suggestedmajor * $targetfactor, 0, PHP_ROUND_HALF_UP);
        return [
            'rawmajor' => $rawmajor,
            'suggestedmajor' => $suggestedmajor,
            'suggestedminor' => $suggestedminor,
            'rate' => $rate,
        ];
    }

    private function apply_rounding(float $amount, string $currency, string $rounding): float {
        $decimals = Currency::minor_unit_exponent($currency);
        if ($rounding === 'whole') {
            return round($amount, 0, PHP_ROUND_HALF_UP);
        }
        if ($rounding === 'ending90' && $decimals >= 1 && $amount >= 1) {
            $whole = floor($amount);
            return $whole + 0.9;
        }
        return round($amount, $decimals, PHP_ROUND_HALF_UP);
    }
}
