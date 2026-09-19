<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\market;

defined('MOODLE_INTERNAL') || die();

/**
 * One market recommendation candidate before surface availability filtering.
 */
final class CommerceMarketCurrencyRecommendation {
    public function __construct(
        private readonly string $country,
        private readonly string $currency,
        private readonly string $source
    ) {
    }

    public function get_country(): string {
        return $this->country;
    }

    public function get_currency(): string {
        return $this->currency;
    }

    public function get_source(): string {
        return $this->source;
    }
}
