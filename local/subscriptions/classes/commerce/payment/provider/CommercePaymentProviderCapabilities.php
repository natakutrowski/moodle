<?php

namespace local_subscriptions\commerce\payment\provider;

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

defined('MOODLE_INTERNAL') || die();

/**
 * Declares the capabilities of a Commerce payment provider.
 */
final class CommercePaymentProviderCapabilities {

    /**
     * @param string[] $currencies
     */
    public function __construct(
        private readonly array $currencies,
        private readonly bool $supportsredirect,
        private readonly bool $supportscancellation,
        private readonly bool $supportsretrieval,
        private readonly bool $supportsrefunds = false,
        private readonly bool $supportsmultiplelines = true,
        private readonly array $metadata = [],
        private readonly array $paymentmethods = [CommercePaymentMethod::CARD]
    ) {
        if ($currencies === []) {
            throw new \coding_exception(
                'A Commerce payment provider must support at least one currency.'
            );
        }

        foreach ($currencies as $currency) {
            if (
                !is_string($currency)
                || !preg_match(
                    '/^[A-Z]{3}$/',
                    strtoupper(trim($currency))
                )
            ) {
                throw new \coding_exception(
                    'A Commerce provider currency must use ISO 4217 format.'
                );
            }
        }

        if ($paymentmethods === []) {
            throw new \coding_exception(
                'A Commerce payment provider must expose at least one payment method.'
            );
        }

        foreach ($paymentmethods as $method) {
            if (!is_string($method)) {
                throw new \coding_exception(
                    'A Commerce payment method key must be a string.'
                );
            }
            CommercePaymentMethod::normalise($method);
        }
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
                )
            )
        );
    }

    public function supports_currency(
        string $currency
    ): bool {
        return in_array(
            strtoupper(trim($currency)),
            $this->get_currencies(),
            true
        );
    }

    public function supports_redirect(): bool {
        return $this->supportsredirect;
    }

    public function supports_cancellation(): bool {
        return $this->supportscancellation;
    }

    public function supports_retrieval(): bool {
        return $this->supportsretrieval;
    }

    public function supports_refunds(): bool {
        return $this->supportsrefunds;
    }

    public function supports_multiple_lines(): bool {
        return $this->supportsmultiplelines;
    }

    /**
     * @return string[]
     */
    public function get_payment_methods(): array {
        return array_values(
            array_unique(
                array_map(
                    static fn(string $method): string =>
                        CommercePaymentMethod::normalise($method),
                    $this->paymentmethods
                )
            )
        );
    }

    public function supports_payment_method(string $method): bool {
        return in_array(
            CommercePaymentMethod::normalise($method),
            $this->get_payment_methods(),
            true
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}