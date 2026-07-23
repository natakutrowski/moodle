<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Non-secret Alfa provider configuration.
 */
final class AlfaPaymentProviderConfiguration {

    /**
     * @param string[] $currencies
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly array $currencies = [
            'RUB',
        ],
        private readonly int $priority = 90
    ) {
        if ($currencies === []) {
            throw new \coding_exception(
                'Alfa must support at least one currency.'
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