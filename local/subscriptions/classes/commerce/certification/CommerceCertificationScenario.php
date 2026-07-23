<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable definition of a Commerce release certification scenario.
 */
final class CommerceCertificationScenario {

    public function __construct(
        private readonly string $key,
        private readonly string $purchasekind,
        private readonly string $provider,
        private readonly string $currency,
        private readonly string $toggle,
        private readonly string $paymentmode,
        private readonly array $checks
    ) {
        if ($key === '' || $toggle === '') {
            throw new \coding_exception('A Commerce certification scenario requires a key and a toggle.');
        }
    }

    public function get_key(): string { return $this->key; }
    public function get_purchase_kind(): string { return $this->purchasekind; }
    public function get_provider(): string { return $this->provider; }
    public function get_currency(): string { return $this->currency; }
    public function get_toggle(): string { return $this->toggle; }
    public function get_payment_mode(): string { return $this->paymentmode; }
    public function get_checks(): array { return $this->checks; }

    public function is_enabled(): bool {
        return !empty(get_config('local_subscriptions', $this->toggle));
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'purchase_kind' => $this->purchasekind,
            'provider' => $this->provider,
            'currency' => $this->currency,
            'toggle' => $this->toggle,
            'enabled' => $this->is_enabled(),
            'payment_mode' => $this->paymentmode,
            'checks' => $this->checks,
        ];
    }
}
