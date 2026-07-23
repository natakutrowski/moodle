<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable definition of a Commerce certification scenario.
 */
final class CommerceCertificationScenario {

    public function __construct(
        private readonly string $key,
        private readonly string $purchasekind,
        private readonly string $provider,
        private readonly string $currency,
        private readonly string $paymentmode,
        private readonly array $checks
    ) {
        if ($key === '') {
            throw new \coding_exception(
                'A Commerce certification scenario requires a key.'
            );
        }
    }

    public function get_key(): string {
        return $this->key;
    }

    public function get_purchase_kind(): string {
        return $this->purchasekind;
    }

    public function get_provider(): string {
        return $this->provider;
    }

    public function get_currency(): string {
        return $this->currency;
    }

    public function get_payment_mode(): string {
        return $this->paymentmode;
    }

    public function get_checks(): array {
        return $this->checks;
    }

    public function is_enabled(): bool {
        return !empty(get_config(
            'local_subscriptions',
            'commerce_checkout_enabled'
        ));
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'purchase_kind' => $this->purchasekind,
            'provider' => $this->provider,
            'currency' => $this->currency,
            'enabled' => $this->is_enabled(),
            'payment_mode' => $this->paymentmode,
            'checks' => $this->checks,
        ];
    }
}