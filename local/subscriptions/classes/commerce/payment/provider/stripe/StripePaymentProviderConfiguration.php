<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Non-secret Stripe provider configuration.
 */
final class StripePaymentProviderConfiguration {

    /**
     * @param string[] $currencies
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly array $currencies = [
            'EUR',
            'USD',
            'GBP',
            'CHF',
        ],
        private readonly int $priority = 100
    ) {
        if ($currencies === []) {
            throw new \coding_exception(
                'Stripe must support at least one currency.'
            );
        }
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function get_priority(): int {
        return $this->priority;
    }

    public function get_currencies(): array {
        return array_values(
            array_unique(
                array_map(
                    static fn(string $currency): string =>
                        strtoupper(trim($currency)),
                    $this->currencies
                )
            )
        );
    }
}