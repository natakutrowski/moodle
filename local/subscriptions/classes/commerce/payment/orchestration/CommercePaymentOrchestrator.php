<?php

namespace local_subscriptions\commerce\payment\orchestration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Single Commerce entry point for payment initialization.
 */
final class CommercePaymentOrchestrator {

    public function __construct(
        private readonly CommercePaymentProviderRegistry
            $providerregistry
    ) {
    }

    /**
     * Resolves, validates and initializes a Commerce payment.
     *
     * This is the only method in the orchestrator allowed to call
     * a remote payment provider.
     */
    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentInitialization {
        $startedat = hrtime(true);

        $provider =
            $this->resolve_provider(
                $request
            );

        $validation =
            $provider->validate(
                $request
            );

        if (!$validation->is_valid()) {
            throw new CommercePaymentValidationException(
                $request->get_reference(),
                $provider->get_key(),
                $validation
            );
        }

        try {
            $paymentresult =
                $provider->initialize(
                    $request,
                    $context
                );
        } catch (
            CommercePaymentOrchestrationException $exception
        ) {
            throw $exception;
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw new CommercePaymentOrchestrationException(
                $exception->getMessage(),
                $exception->get_provider_code()
                    ?? 'provider_initialization_failed',
                $request->get_reference(),
                $exception->get_provider_key()
                    ?? $provider->get_key(),
                array_merge(
                    $exception->get_context(),
                    [
                        'idempotencykey' =>
                            $context
                                ->get_idempotency_key(),
                    ]
                ),
                $exception
            );
        } catch (\Throwable $exception) {
            throw new CommercePaymentOrchestrationException(
                'Unexpected Commerce payment initialization failure.',
                'unexpected_initialization_failure',
                $request->get_reference(),
                $provider->get_key(),
                [
                    'idempotencykey' =>
                        $context
                            ->get_idempotency_key(),
                ],
                $exception
            );
        }

        $this->assert_result_consistency(
            $request,
            $provider,
            $paymentresult
        );

        return new CommercePaymentInitialization(
            $request->get_reference(),
            $provider->get_key(),
            $validation,
            $paymentresult,
            false,
            $this->duration_ms(
                $startedat
            ),
            [
                'idempotencykey' =>
                    $context
                        ->get_idempotency_key(),

                'live' =>
                    $context->is_live(),

                'providerpriority' =>
                    $provider
                        ->get_priority(),
            ]
        );
    }

    /**
     * Resolves and validates a payment without calling Stripe or Alfa.
     */
    public function simulate(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentInitialization {
        $startedat = hrtime(true);

        $provider =
            $this->resolve_provider(
                $request
            );

        $validation =
            $provider->validate(
                $request
            );

        return new CommercePaymentInitialization(
            $request->get_reference(),
            $provider->get_key(),
            $validation,
            null,
            true,
            $this->duration_ms(
                $startedat
            ),
            [
                'idempotencykey' =>
                    $context
                        ->get_idempotency_key(),

                'live' =>
                    $context->is_live(),

                'providerpriority' =>
                    $provider
                        ->get_priority(),

                'supported' =>
                    $provider->supports(
                        $request
                    ),
            ]
        );
    }

    private function resolve_provider(
        CommercePaymentRequest $request
    ): CommercePaymentProvider {
        try {
            return $this
                ->providerregistry
                ->resolve(
                    $request
                );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw new CommercePaymentOrchestrationException(
                $exception->getMessage(),
                $exception->get_provider_code()
                    ?? 'provider_resolution_failed',
                $request->get_reference(),
                $exception->get_provider_key(),
                $exception->get_context(),
                $exception
            );
        } catch (\Throwable $exception) {
            throw new CommercePaymentOrchestrationException(
                'Commerce could not resolve a payment provider.',
                'provider_resolution_failed',
                $request->get_reference(),
                $request->get_preferred_provider(),
                [
                    'currency' =>
                        $request->get_currency(),

                    'amountminor' =>
                        $request->get_amount_minor(),
                ],
                $exception
            );
        }
    }

    private function assert_result_consistency(
        CommercePaymentRequest $request,
        CommercePaymentProvider $provider,
        CommercePaymentResult $result
    ): void {
        if (
            $result->get_request_reference()
            !== $request->get_reference()
        ) {
            throw new CommercePaymentOrchestrationException(
                'The provider returned a result for another payment request.',
                'provider_result_reference_mismatch',
                $request->get_reference(),
                $provider->get_key(),
                [
                    'returnedreference' =>
                        $result
                            ->get_request_reference(),
                ]
            );
        }

        if (
            $result->get_provider_key()
            !== $provider->get_key()
        ) {
            throw new CommercePaymentOrchestrationException(
                'The provider returned an inconsistent provider key.',
                'provider_result_key_mismatch',
                $request->get_reference(),
                $provider->get_key(),
                [
                    'returnedprovider' =>
                        $result
                            ->get_provider_key(),
                ]
            );
        }

        if (
            $result->requires_customer_action()
        ) {
            $action =
                $result->get_action();

            if (!$action instanceof CommercePaymentAction) {
                throw new CommercePaymentOrchestrationException(
                    'The provider requires customer action but returned no action.',
                    'provider_action_missing',
                    $request->get_reference(),
                    $provider->get_key()
                );
            }

            if (
                $action->is_redirect()
                && $action->get_url() === null
            ) {
                throw new CommercePaymentOrchestrationException(
                    'The provider returned an empty payment redirect URL.',
                    'provider_redirect_url_missing',
                    $request->get_reference(),
                    $provider->get_key()
                );
            }
        }
    }

    private function duration_ms(
        int $startedat
    ): int {
        return max(
            0,
            (int)round(
                (hrtime(true) - $startedat)
                / 1_000_000
            )
        );
    }
}