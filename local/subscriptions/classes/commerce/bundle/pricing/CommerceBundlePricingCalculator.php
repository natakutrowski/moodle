<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Pure integer-based Bundle pricing calculator. */
final class CommerceBundlePricingCalculator {
    public function calculate(
        string $sku,
        CommerceBundlePricingConfiguration $configuration,
        CommerceMoney $componenttotal,
        ?CommerceMoney $fixedprice,
        array $lines = [],
        bool $componentcomparisoncomplete = true
    ): CommerceBundlePriceQuote {
        $strategy = $configuration->get_strategy();

        if ($strategy === CommerceBundlePricingStrategy::FIXED) {
            if ($fixedprice === null) {
                throw new \coding_exception('A fixed Bundle price requires an active catalogue price.');
            }
            if ($fixedprice->get_currency() !== $componenttotal->get_currency()) {
                throw new \coding_exception('Bundle fixed price currency does not match component currency.');
            }
            $final = $fixedprice;
        } else if ($strategy === CommerceBundlePricingStrategy::COMPONENT_SUM) {
            $final = $componenttotal;
        } else {
            $remainingbps = 10000 - $configuration->get_discount_bps();
            $amountminor = intdiv(
                ($componenttotal->get_amount_minor() * $remainingbps) + 5000,
                10000
            );
            $final = CommerceMoney::from_minor($amountminor, $componenttotal->get_currency());
        }

        return new CommerceBundlePriceQuote(
            strtoupper(trim($sku)),
            $configuration,
            $componenttotal,
            $final,
            $lines,
            $componentcomparisoncomplete
        );
    }
}
