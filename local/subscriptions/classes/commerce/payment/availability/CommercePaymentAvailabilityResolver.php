<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\availability;

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Central provider × currency × payment-method resolver.
 *
 * It does not call a PSP. It only tells Commerce which customer-facing
 * methods are usable and which providers can fulfil each method.
 */
final class CommercePaymentAvailabilityResolver {
    public function __construct(
        private readonly CommercePaymentProviderRegistry $providers,
        ?CommercePaymentPresentationPolicy $presentationpolicy = null
    ) {
        $this->presentationpolicy =
            $presentationpolicy
            ?? new CommercePaymentPresentationPolicy();
    }

    private readonly CommercePaymentPresentationPolicy $presentationpolicy;

    /**
     * All known methods, including unavailable ones.
     *
     * @return CommercePaymentMethodAvailability[]
     */
    public function resolve(
        string $currency,
        ?string $country = null
    ): array {
        $currency = Currency::sanitize($currency);
        if ($currency === '') {
            throw new \coding_exception(
                'Payment availability requires a valid currency.'
            );
        }

        $result = [];

        foreach (CommercePaymentMethodCatalogue::keys() as $method) {
            $result[] = new CommercePaymentMethodAvailability(
                $method,
                $currency,
                $this->provider_keys_for(
                    $currency,
                    $method,
                    $country
                )
            );
        }

        return $result;
    }

    /**
     * Available customer-facing methods only.
     *
     * @return CommercePaymentMethodAvailability[]
     */
    public function available(
        string $currency,
        ?string $country = null
    ): array {
        return array_values(
            array_filter(
                $this->resolve(
                    $currency,
                    $country
                ),
                static fn(
                    CommercePaymentMethodAvailability $availability
                ): bool => $availability->is_available()
            )
        );
    }

    public function method(
        string $currency,
        string $method,
        ?string $country = null
    ): CommercePaymentMethodAvailability {
        $currency = Currency::sanitize($currency);
        if ($currency === '') {
            throw new \coding_exception(
                'Payment availability requires a valid currency.'
            );
        }

        $method = CommercePaymentMethod::normalise($method);

        foreach (
            $this->resolve(
                $currency,
                $country
            )
            as $availability
        ) {
            if ($availability->get_method() === $method) {
                return $availability;
            }
        }

        return new CommercePaymentMethodAvailability(
            $method,
            $currency,
            $this->provider_keys_for(
                $currency,
                $method,
                $country
            )
        );
    }

    /**
     * @return string[]
     */
    private function provider_keys_for(
        string $currency,
        string $method,
        ?string $country = null
    ): array {
        if (
            !CommercePaymentMethodMarketEligibility::supports(
                $method,
                $currency,
                $country
            )
        ) {
            return [];
        }

        if (
            !$this->presentationpolicy
                ->is_method_allowed(
                    $method
                )
        ) {
            return [];
        }

        $providers = array_values(
            array_filter(
                $this->providers->all(),
                fn(
                    CommercePaymentProvider $provider
                ): bool =>
                    $provider->is_available()
                    && $this->presentationpolicy
                        ->is_provider_allowed(
                            $provider->get_key()
                        )
                    && $provider
                        ->get_capabilities()
                        ->supports_currency($currency)
                    && $provider
                        ->get_capabilities()
                        ->supports_payment_method($method)
            )
        );

        usort(
            $providers,
            static fn(
                CommercePaymentProvider $left,
                CommercePaymentProvider $right
            ): int =>
                $right->get_priority()
                <=> $left->get_priority()
        );

        return array_map(
            static fn(
                CommercePaymentProvider $provider
            ): string => $provider->get_key(),
            $providers
        );
    }
}
