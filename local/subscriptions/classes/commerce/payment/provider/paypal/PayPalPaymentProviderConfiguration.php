<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

/**
 * Non-secret PayPal provider configuration.
 *
 * F1 deliberately keeps routing disabled. F2 will turn this into an active
 * provider only after Orders v2 initialization/capture are implemented.
 */
final class PayPalPaymentProviderConfiguration {
    public function __construct(
        private readonly bool $enabled = false,
        private readonly ?array $currencies = null,
        private readonly int $priority = 80
    ) {
        if ($currencies !== null && $currencies === []) {
            throw new \coding_exception(
                'PayPal must support at least one currency.'
            );
        }
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function get_priority(): int {
        return $this->priority;
    }

    /**
     * @return string[]
     */
    public function get_currencies(): array {
        return array_values(
            array_unique(
                array_map(
                    static fn(string $currency): string =>
                        strtoupper(trim($currency)),
                    $this->currencies
                        ?? PayPalSupportedCurrencies::all()
                )
            )
        );
    }
}
