<?php

namespace local_subscriptions\commerce\payment\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;

/**
 * Evaluates Commerce payment compatibility without creating a payment.
 */
final class CommercePaymentShadowService {

    public function __construct(
        private readonly LegacyCommercePaymentRequestFactory
            $requestfactory,
        private readonly CommercePaymentProviderContextFactory
            $contextfactory,
        private readonly CommercePaymentOrchestrator
            $orchestrator
    ) {
    }

    public function evaluate(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $legacyoptions,
        bool $live
    ): CommercePaymentShadowReport {
        $legacyid =
            (int)($paymentrequest->id ?? 0);

        $legacyprovider =
            strtolower(
                trim(
                    (string)(
                        $paymentrequest
                            ->payment_provider
                        ?? ''
                    )
                )
            );

        $legacycurrency =
            strtoupper(
                trim(
                    (string)(
                        $paymentrequest->currency
                        ?? ''
                    )
                )
            );

        $legacyamountminor =
            isset($paymentrequest->amount_minor)
                ? (int)$paymentrequest->amount_minor
                : (int)round(
                    (float)(
                        $paymentrequest
                            ->locked_final_price
                        ?? $paymentrequest->price
                        ?? 0
                    ) * 100
                );

        try {
            $request =
                $this->requestfactory->create(
                    $paymentrequest,
                    $paymentrequesttable,
                    $legacyoptions
                );

            $context =
                $this->contextfactory->create(
                    $request,
                    $live,
                    [
                        'shadow' =>
                            true,
                    ]
                );

            $simulation =
                $this->orchestrator->simulate(
                    $request,
                    $context
                );

            $differences = [];

            if (
                $simulation->get_provider_key()
                !== $legacyprovider
            ) {
                $differences[] = [
                    'field' =>
                        'provider',

                    'legacy' =>
                        $legacyprovider,

                    'commerce' =>
                        $simulation
                            ->get_provider_key(),
                ];
            }

            if (
                $request->get_currency()
                !== $legacycurrency
            ) {
                $differences[] = [
                    'field' =>
                        'currency',

                    'legacy' =>
                        $legacycurrency,

                    'commerce' =>
                        $request
                            ->get_currency(),
                ];
            }

            if (
                $request->get_amount_minor()
                !== $legacyamountminor
            ) {
                $differences[] = [
                    'field' =>
                        'amountminor',

                    'legacy' =>
                        $legacyamountminor,

                    'commerce' =>
                        $request
                            ->get_amount_minor(),
                ];
            }

            return new CommercePaymentShadowReport(
                $legacyid,
                $paymentrequesttable,
                $legacyprovider,
                $legacycurrency,
                $legacyamountminor,
                $simulation,
                $differences
            );
        } catch (\Throwable $exception) {
            return new CommercePaymentShadowReport(
                $legacyid,
                $paymentrequesttable,
                $legacyprovider,
                $legacycurrency,
                $legacyamountminor,
                null,
                [],
                [
                    [
                        'class' =>
                            get_class($exception),

                        'message' =>
                            $exception->getMessage(),
                    ],
                ]
            );
        }
    }
}