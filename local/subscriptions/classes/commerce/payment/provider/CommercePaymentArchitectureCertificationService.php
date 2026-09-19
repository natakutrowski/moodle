<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider;

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only structural certification of the payment architecture.
 *
 * This service deliberately knows nothing about concrete provider classes.
 * Every current or future provider is certified against the same contracts.
 */
final class CommercePaymentArchitectureCertificationService {
    public function __construct(
        private readonly CommercePaymentProviderRegistry $providers,
        private readonly CommerceCurrencyRegistry $currencies
    ) {
    }

    /**
     * @return array{
     *   certified:bool,
     *   errors:array<int,array{code:string,message:string,provider:?string}>,
     *   warnings:array<int,array{code:string,message:string,provider:?string}>,
     *   enabledcurrencies:string[],
     *   uncoveredcurrencies:string[]
     * }
     */
    public function certify(): array {
        $errors = [];
        $warnings = [];
        $enabledcurrencies = $this->currencies->enabled();

        foreach ($this->providers->all() as $provider) {
            $key = $provider->get_key();
            $capabilities = $provider->get_capabilities();

            if (!preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
                $errors[] = $this->issue(
                    'invalid_provider_key',
                    'Invalid provider key: ' . $key,
                    $key
                );
            }

            $providercurrencies = $capabilities->get_currencies();
            if ($providercurrencies === []) {
                $errors[] = $this->issue(
                    'provider_without_currency',
                    'Provider exposes no currency.',
                    $key
                );
            }

            foreach ($providercurrencies as $currency) {
                if (!Currency::is_known($currency)) {
                    $errors[] = $this->issue(
                        'unknown_currency',
                        'Provider exposes an unknown Commerce currency: '
                            . $currency,
                        $key
                    );
                }
            }

            $methods = $capabilities->get_payment_methods();
            if ($methods === []) {
                $errors[] = $this->issue(
                    'provider_without_method',
                    'Provider exposes no payment method.',
                    $key
                );
            }

            foreach ($methods as $method) {
                if (!CommercePaymentMethod::is_known($method)) {
                    $warnings[] = $this->issue(
                        'extension_payment_method',
                        'Provider exposes a payment method not yet present in '
                            . 'the core customer-facing method catalogue: '
                            . $method,
                        $key
                    );
                }
            }

            $refundcapability = $capabilities->supports_refunds();
            $refundcontract =
                $provider instanceof CommerceRefundCapablePaymentProvider;
            $historycontract =
                $provider instanceof CommerceRefundHistoryCapablePaymentProvider;

            if ($refundcapability !== $refundcontract) {
                $errors[] = $this->issue(
                    'refund_contract_mismatch',
                    'Refund capability and Commerce refund contract disagree.',
                    $key
                );
            }

            if ($historycontract && !$refundcontract) {
                $errors[] = $this->issue(
                    'refund_history_without_refund',
                    'Refund history contract requires the refund contract.',
                    $key
                );
            }

            if (
                $provider->is_available()
                && array_intersect(
                    $providercurrencies,
                    $enabledcurrencies
                ) === []
            ) {
                $warnings[] = $this->issue(
                    'provider_without_enabled_currency',
                    'Provider is available but supports none of the currently '
                        . 'enabled Commerce currencies.',
                    $key
                );
            }
        }

        // A tie for the same currency + method is not safe because automatic
        // provider resolution would be ambiguous.
        foreach ($enabledcurrencies as $currency) {
            $groups = [];

            foreach ($this->providers->all() as $provider) {
                if (
                    !$provider->is_available()
                    || !$provider->get_capabilities()->supports_currency(
                        $currency
                    )
                ) {
                    continue;
                }

                foreach (
                    $provider->get_capabilities()->get_payment_methods()
                    as $method
                ) {
                    $priority = $provider->get_priority();
                    $groups[$method][$priority][] = $provider->get_key();
                }
            }

            foreach ($groups as $method => $priorities) {
                foreach ($priorities as $priority => $providerkeys) {
                    if (count($providerkeys) < 2) {
                        continue;
                    }

                    $errors[] = $this->issue(
                        'provider_priority_tie',
                        sprintf(
                            'Ambiguous providers for %s/%s at priority %d: %s',
                            $currency,
                            $method,
                            $priority,
                            implode(', ', $providerkeys)
                        ),
                        null
                    );
                }
            }
        }

        $uncovered = [];
        foreach ($enabledcurrencies as $currency) {
            $covered = false;

            foreach ($this->providers->all() as $provider) {
                if (
                    $provider->is_available()
                    && $provider->get_capabilities()->supports_currency(
                        $currency
                    )
                ) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                $uncovered[] = $currency;
            }
        }

        if ($uncovered !== []) {
            $warnings[] = $this->issue(
                'enabled_currency_without_provider',
                'Enabled currencies without an available payment provider: '
                    . implode(', ', $uncovered),
                null
            );
        }

        return [
            'certified' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'enabledcurrencies' => $enabledcurrencies,
            'uncoveredcurrencies' => $uncovered,
        ];
    }

    /**
     * @return array{code:string,message:string,provider:?string}
     */
    private function issue(
        string $code,
        string $message,
        ?string $provider
    ): array {
        return [
            'code' => $code,
            'message' => $message,
            'provider' => $provider,
        ];
    }
}
