<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\availability;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent availability of one customer-facing payment method.
 */
final class CommercePaymentMethodAvailability {
    /**
     * @param string[] $providerkeys Ordered compatible provider keys.
     */
    public function __construct(
        private readonly string $method,
        private readonly string $currency,
        private readonly array $providerkeys
    ) {
        if (trim($method) === '') {
            throw new \coding_exception(
                'A payment method availability requires a method key.'
            );
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception(
                'A payment method availability requires an ISO currency.'
            );
        }
    }

    public function get_method(): string {
        return strtolower(trim($this->method));
    }

    public function get_currency(): string {
        return strtoupper(trim($this->currency));
    }

    public function is_available(): bool {
        return $this->providerkeys !== [];
    }

    /**
     * @return string[]
     */
    public function get_provider_keys(): array {
        return array_values($this->providerkeys);
    }

    public function get_preferred_provider_key(): ?string {
        return $this->providerkeys[0] ?? null;
    }
}
