<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider;

use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only architecture view used by E1 diagnostics and tests.
 */
final class CommercePaymentArchitectureInspector {
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
     * @return array<int,array<string,mixed>>
     */
    public function providers(): array {
        $result = [];

        foreach ($this->providers->all() as $provider) {
            $capabilities = $provider->get_capabilities();

            $result[] = [
                'key' => $provider->get_key(),
                'priority' => $provider->get_priority(),
                'available' => $provider->is_available(),
                'adminallowed' =>
                    $this->presentationpolicy
                        ->is_provider_allowed(
                            $provider->get_key()
                        ),
                'currencies' => $capabilities->get_currencies(),
                'paymentmethods' => $capabilities->get_payment_methods(),
                'redirect' => $capabilities->supports_redirect(),
                'retrieval' => $capabilities->supports_retrieval(),
                'cancellation' => $capabilities->supports_cancellation(),
                'refunds' => $capabilities->supports_refunds(),
                'refundcontract' =>
                    $provider instanceof CommerceRefundCapablePaymentProvider,
                'refundcertified' =>
                    $capabilities->supports_refunds()
                    && $provider instanceof CommerceRefundCapablePaymentProvider,
                'refundhistorycontract' =>
                    $provider instanceof CommerceRefundHistoryCapablePaymentProvider,
                'metadata' => $capabilities->get_metadata(),
            ];
        }

        return $result;
    }

    /**
     * Provider-independent payment-method availability for one currency.
     *
     * @return array<int,array{
     *   method:string,
     *   available:bool,
     *   preferredprovider:?string,
     *   providers:string[]
     * }>
     */
    public function methods(string $currency): array {
        $resolver = new CommercePaymentAvailabilityResolver(
            $this->providers
        );

        return array_map(
            function($availability): array {
                $definition = CommercePaymentMethodCatalogue::get(
                    $availability->get_method()
                );

                return [
                    'method' => $availability->get_method(),
                    'family' => $definition->get_family(),
                    'wallet' => $definition->is_wallet(),
                    'deferred' => $definition->is_deferred(),
                    'presentation' => $definition->get_presentation(),
                    'marketdependent' =>
                        CommercePaymentMethodMarketEligibility
                            ::is_market_dependent(
                                $availability->get_method()
                            ),
                    'adminallowed' =>
                        $this->presentationpolicy
                            ->is_method_allowed(
                                $availability->get_method()
                            ),
                    'available' => $availability->is_available(),
                    'preferredprovider' =>
                        $availability->get_preferred_provider_key(),
                    'providers' =>
                        $availability->get_provider_keys(),
                ];
            },
            $resolver->resolve($currency)
        );
    }

}
