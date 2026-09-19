<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

final class CommerceCurrencySelectionResult {
    public function __construct(
        private readonly string $currency,
        private readonly CommerceCurrencySelectionSource $source
    ) {
    }

    public function get_currency(): string {
        return $this->currency;
    }

    public function get_source(): CommerceCurrencySelectionSource {
        return $this->source;
    }

    public function get_source_value(): string {
        return $this->source->value;
    }
}
