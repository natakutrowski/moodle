<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\execution;

use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration;

defined('MOODLE_INTERNAL') || die();

final class CommerceCheckoutPaymentOrchestrator {
    public function __construct(
        private readonly CommercePaymentAvailabilityResolver $availability
    ) {}

    public function route_for(
        string $currency,
        string $method,
        ?string $country = null
    ): ?CommerceCheckoutPaymentRoute {
        $availability = $this->availability->method(
            $currency,
            $method,
            $country
        );

        if (!$availability->is_available()) {
            return null;
        }

        $provider = trim(
            (string)$availability->get_preferred_provider_key()
        );

        if ($provider === '') {
            return null;
        }

        return $this->route(
            $availability->get_method(),
            $provider
        );
    }

    /**
     * @return CommerceCheckoutPaymentRoute[]
     */
    public function routes(
        string $currency,
        ?string $country = null
    ): array {
        $routes = [];

        foreach ($this->availability->available($currency, $country) as $availability) {
            $provider = trim(
                (string)$availability->get_preferred_provider_key()
            );

            if ($provider === '') {
                continue;
            }

            $routes[] = $this->route(
                $availability->get_method(),
                $provider
            );
        }

        return $routes;
    }

    private function route(
        string $method,
        string $provider
    ): CommerceCheckoutPaymentRoute {
        $method = CommercePaymentMethod::normalise($method);
        $provider = strtolower(trim($provider));

        if (
            $provider === 'stripe'
            && in_array(
                $method,
                [
                    CommercePaymentMethod::APPLE_PAY,
                    CommercePaymentMethod::GOOGLE_PAY,
                    CommercePaymentMethod::LINK,
                    CommercePaymentMethod::KLARNA,
                ],
                true
            )
        ) {
            $surface = CommerceCheckoutPaymentRoute::SURFACE_EXPRESS;
        } else if (
            $provider === 'stripe'
            && $method === CommercePaymentMethod::CARD
        ) {
            $surface = CommerceCheckoutPaymentRoute::SURFACE_INLINE;
        } else if (
            $provider === 'alfa'
            && $method === CommercePaymentMethod::CARD
            && AlfaWidgetConfiguration::is_available()
        ) {
            $surface = CommerceCheckoutPaymentRoute::SURFACE_INLINE;
        } else {
            $surface = CommerceCheckoutPaymentRoute::SURFACE_HOSTED;
        }

        return new CommerceCheckoutPaymentRoute(
            $method,
            $provider,
            $surface
        );
    }
}
