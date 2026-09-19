<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\merchant;

use local_subscriptions\commerce\currency\market\CommerceMarketCountryResolver;

defined('MOODLE_INTERNAL') || die();

/** Bridges the existing H market-country resolver to the legal merchant policy. */
final class CommerceMerchantMarketResolver {
    public function __construct(
        private readonly ?CommerceMarketCountryResolver $countryresolver = null,
        private readonly ?CommerceMerchantResolver $merchantresolver = null
    ) {
    }

    public function resolve(
        string $currency = '',
        string $provider = '',
        array $metadata = []
    ): CommerceMerchantResolutionResult {
        $countryresolver = $this->countryresolver ?? new CommerceMarketCountryResolver();
        $merchantresolver = $this->merchantresolver ?? new CommerceMerchantResolver();

        return $merchantresolver->resolve(new CommerceMerchantResolutionContext(
            $countryresolver->resolve(),
            $currency,
            $provider,
            $metadata
        ));
    }
}
