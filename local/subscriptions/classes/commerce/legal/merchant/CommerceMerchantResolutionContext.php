<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\merchant;

defined('MOODLE_INTERNAL') || die();

/** Immutable inputs available to merchant/legal-entity resolution. */
final class CommerceMerchantResolutionContext {
    public function __construct(
        private readonly string $marketcountry,
        private readonly string $currency = '',
        private readonly string $provider = '',
        private readonly array $metadata = []
    ) {
        $country = strtoupper(trim($marketcountry));
        if ($country !== 'ZZ' && preg_match('/^[A-Z]{2}$/', $country) !== 1) {
            throw new \coding_exception('Commerce merchant market country must use ISO 3166-1 alpha-2 or ZZ.');
        }

        $currency = strtoupper(trim($currency));
        if ($currency !== '' && preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new \coding_exception('Commerce merchant currency must use ISO 4217 format when provided.');
        }
    }

    public function get_market_country(): string {
        return strtoupper(trim($this->marketcountry));
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function get_provider(): string {
        return strtolower(trim($this->provider));
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
