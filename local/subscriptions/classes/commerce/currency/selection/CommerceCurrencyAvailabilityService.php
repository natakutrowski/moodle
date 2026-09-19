<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\currency\Currency;

/**
 * H13.1.5 — canonical intersection between surface currencies and Commerce config.
 */
final class CommerceCurrencyAvailabilityService {
    public function __construct(
        private readonly CommerceCurrencyRegistry $registry = new CommerceCurrencyRegistry()
    ) {
    }

    /**
     * @param string[] $available
     * @return string[]
     */
    public function enabled_from(array $available): array {
        $enabled = $this->registry->enabled();

        return array_values(array_unique(array_filter(array_map(
            static fn(mixed $value): string => Currency::sanitize((string)$value),
            $available
        ), static fn(string $currency): bool =>
            $currency !== '' && in_array($currency, $enabled, true)
        )));
    }
}
