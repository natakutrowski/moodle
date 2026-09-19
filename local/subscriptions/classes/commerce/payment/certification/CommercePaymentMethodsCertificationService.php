<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\certification;

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentMethodsCertificationService {
    public function __construct(
        private readonly CommercePaymentProviderRegistry $providers,
        private readonly CommercePaymentPresentationPolicy $presentation
    ) {
    }

    public function certify(): CommercePaymentMethodsCertification {
        $errors = [];
        $warnings = [];
        $checks = [];

        $expected = [
            CommercePaymentMethod::CARD,
            CommercePaymentMethod::APPLE_PAY,
            CommercePaymentMethod::GOOGLE_PAY,
            CommercePaymentMethod::PAYPAL,
            CommercePaymentMethod::LINK,
            CommercePaymentMethod::KLARNA,
        ];

        $catalogue = CommercePaymentMethodCatalogue::keys();
        $checks['catalogue_complete'] = $catalogue === $expected;

        if (!$checks['catalogue_complete']) {
            $errors[] = 'payment_method_catalogue_incomplete';
        }

        foreach (
            [
                Provider::STRIPE,
                Provider::ALFA,
                Provider::PAYPAL,
            ]
            as $providerkey
        ) {
            $registered = $this->providers->has($providerkey);
            $checks[$providerkey . '_registered'] = $registered;

            if (!$registered) {
                $errors[] = $providerkey . '_not_registered';
            }
        }

        if ($this->providers->has(Provider::STRIPE)) {
            $stripe = $this->providers->get(Provider::STRIPE);

            foreach (
                [
                    CommercePaymentMethod::CARD,
                    CommercePaymentMethod::APPLE_PAY,
                    CommercePaymentMethod::GOOGLE_PAY,
                    CommercePaymentMethod::LINK,
                    CommercePaymentMethod::KLARNA,
                ]
                as $method
            ) {
                $key = 'stripe_supports_' . $method;
                $checks[$key] = $stripe->get_capabilities()
                    ->supports_payment_method($method);

                if (!$checks[$key]) {
                    $errors[] = $key . '_failed';
                }
            }

            $checks['stripe_does_not_claim_paypal'] =
                !$stripe->get_capabilities()
                    ->supports_payment_method(
                        CommercePaymentMethod::PAYPAL
                    );

            if (!$checks['stripe_does_not_claim_paypal']) {
                $errors[] = 'stripe_paypal_capability_leak';
            }
        }

        if ($this->providers->has(Provider::ALFA)) {
            $alfa = $this->providers->get(Provider::ALFA);
            // Alfa Pay is an optional fast rail: it is exposed only when
            // the active environment has the required -api user/password.
            // Certification therefore locks the mandatory Card capability
            // and prevents unrelated-method leakage without forcing Alfa Pay
            // on environments where it is intentionally not configured.
            $checks['alfa_supported_methods'] =
                $alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::CARD)
                && !$alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::PAYPAL)
                && !$alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::APPLE_PAY)
                && !$alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::GOOGLE_PAY)
                && !$alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::LINK)
                && !$alfa->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::KLARNA);

            if (!$checks['alfa_supported_methods']) {
                $errors[] = 'alfa_method_capability_mismatch';
            }
        }

        if ($this->providers->has(Provider::PAYPAL)) {
            $paypal = $this->providers->get(Provider::PAYPAL);
            $checks['paypal_method_only'] =
                $paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::PAYPAL)
                && !$paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::CARD)
                && !$paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::APPLE_PAY)
                && !$paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::GOOGLE_PAY)
                && !$paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::LINK)
                && !$paypal->get_capabilities()
                    ->supports_payment_method(CommercePaymentMethod::KLARNA);

            if (!$checks['paypal_method_only']) {
                $errors[] = 'paypal_method_capability_mismatch';
            }
        }

        $checks['klarna_fr_eur'] =
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'FR'
            );
        $checks['klarna_rub_blocked'] =
            !CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'RUB',
                'FR'
            );
        $checks['klarna_ru_market_blocked'] =
            !CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'RU'
            );

        foreach (
            [
                'klarna_fr_eur',
                'klarna_rub_blocked',
                'klarna_ru_market_blocked',
            ]
            as $check
        ) {
            if (empty($checks[$check])) {
                $errors[] = $check . '_failed';
            }
        }

        $allowedmethods = $this->presentation->allowed_methods();
        $allowedproviders = $this->presentation->allowed_providers();

        $checks['admin_methods'] = $allowedmethods;
        $checks['admin_providers'] = $allowedproviders;

        if ($allowedmethods === []) {
            $warnings[] = 'no_customer_payment_method_allowed';
        }

        if ($allowedproviders === []) {
            $warnings[] = 'no_customer_payment_provider_allowed';
        }

        foreach ($allowedmethods as $method) {
            if (!in_array($method, $catalogue, true)) {
                $errors[] = 'unknown_admin_method_' . $method;
            }
        }

        foreach ($allowedproviders as $provider) {
            if (!in_array(
                $provider,
                [Provider::STRIPE, Provider::ALFA, Provider::PAYPAL],
                true
            )) {
                $errors[] = 'unknown_admin_provider_' . $provider;
            }
        }

        return new CommercePaymentMethodsCertification(
            $errors === [],
            array_values(array_unique($errors)),
            array_values(array_unique($warnings)),
            $checks
        );
    }
}
