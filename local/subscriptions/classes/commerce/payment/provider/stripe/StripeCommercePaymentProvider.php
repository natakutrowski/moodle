<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRequest;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;

/**
 * Commerce adapter for the existing Stripe payment integration.
 */
final class StripeCommercePaymentProvider
    implements CommercePaymentProvider, CommerceRefundCapablePaymentProvider, CommerceRefundHistoryCapablePaymentProvider {

    public const KEY = 'stripe';

    public function __construct(
        private readonly StripePaymentGateway $gateway,
        private readonly StripePaymentProviderConfiguration $configuration
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function get_priority(): int {
        return $this->configuration->get_priority();
    }

    public function is_available(): bool {
        return $this->configuration->is_enabled()
            && $this->gateway->is_configured();
    }

    public function get_capabilities():
        CommercePaymentProviderCapabilities {
        return new CommercePaymentProviderCapabilities(
            $this->configuration->get_currencies(),

            // Hosted Checkout redirection.
            true,

            // Payment cancellation is not exposed by the Legacy gateway.
            false,

            // Synchronous payment retrieval is not exposed yet.
            false,

            // Certified through CommerceRefundCapablePaymentProvider.
            true,

            // Commerce may aggregate several lines into one Stripe item.
            true,

            [
                'checkout_mode' =>
                    'hosted',

                'provider' =>
                    self::KEY,

                'confirmation_mode' =>
                    'legacy_webhook',

                'bridge' =>
                    'legacy',
            ],
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::KLARNA,
            ]
        );
    }

    public function supports(
        CommercePaymentRequest $request
    ): bool {
        if (!$request->requires_payment()) {
            return false;
        }

        $capabilities = $this->get_capabilities();

        if (!$capabilities->supports_currency($request->get_currency())) {
            return false;
        }

        $method = $request->get_preferred_payment_method();
        return $method === null
            || $capabilities->supports_payment_method($method);
    }

    public function validate(
        CommercePaymentRequest $request
    ): CommercePaymentProviderValidationResult {
        $result =
            CommercePaymentProviderValidationResult::valid();

        if (!$this->configuration->is_enabled()) {
            $result->add_error(
                'stripe_disabled',
                'The Stripe Commerce provider is disabled.'
            );
        }

        if (!$this->gateway->is_configured()) {
            $result->add_error(
                'stripe_not_configured',
                'The Stripe gateway is not configured.'
            );
        }

        if (!$request->requires_payment()) {
            $result->add_error(
                'stripe_free_payment_not_supported',
                'Stripe must not initialize zero-amount payments.'
            );
        }

        if (
            !$this
                ->get_capabilities()
                ->supports_currency(
                    $request->get_currency()
                )
        ) {
            $result->add_error(
                'stripe_currency_not_supported',
                'Stripe does not support the requested currency.',
                [
                    'currency' => $request->get_currency(),
                ]
            );
        }

        if ($request->get_return_url() === null) {
            $result->add_error(
                'stripe_return_url_missing',
                'Stripe requires a payment return URL.'
            );
        }

        if ($request->get_cancel_url() === null) {
            $result->add_error(
                'stripe_cancel_url_missing',
                'Stripe requires a payment cancellation URL.'
            );
        }

        return $result;
    }

    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $validation = $this->validate($request);

        if (!$validation->is_valid()) {
            throw new CommercePaymentProviderException(
                'The Stripe payment request is invalid.',
                self::KEY,
                'stripe_validation_failed',
                [
                    'validation' => $validation->to_array(),
                ]
            );
        }

        try {
            $gatewayrequest =
                $this->build_gateway_request(
                    $request,
                    $context
                );

            $executionmode = trim(
                (string)$request->get_metadata_value(
                    'payment_execution_mode',
                    ''
                )
            );

            $response =
                $executionmode
                    === CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED
                && in_array(
                    $request->get_preferred_payment_method(),
                    [
                        CommercePaymentMethod::CARD,
                        CommercePaymentMethod::APPLE_PAY,
                        CommercePaymentMethod::GOOGLE_PAY,
                        CommercePaymentMethod::LINK,
                        CommercePaymentMethod::KLARNA,
                    ],
                    true
                )
                    ? $this->gateway->create_payment_intent(
                        $gatewayrequest
                    )
                    : $this->gateway->create_checkout_session(
                        $gatewayrequest
                    );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Stripe checkout session creation failed.',
                self::KEY,
                'stripe_initialization_failed',
                [
                    'requestreference' =>
                        $request->get_reference(),
                ],
                $exception
            );
        }

        return $this->map_response(
            $request->get_reference(),
            $response
        );
    }

    public function retrieve(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $providerpaymentid = trim($providerpaymentid);

        if ($providerpaymentid === '') {
            throw new CommercePaymentProviderException(
                'The Stripe payment identifier cannot be empty.',
                self::KEY,
                'stripe_payment_id_missing'
            );
        }

        try {
            $response =
                $this->gateway->retrieve(
                    $providerpaymentid
                );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Stripe payment retrieval failed.',
                self::KEY,
                'stripe_retrieval_failed',
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ],
                $exception
            );
        }

        return $this->map_response(
            $this->resolve_request_reference(
                $response,
                $providerpaymentid
            ),
            $response
        );
    }

    public function cancel(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult {
        $providerpaymentid = trim($providerpaymentid);

        if ($providerpaymentid === '') {
            throw new CommercePaymentProviderException(
                'The Stripe payment identifier cannot be empty.',
                self::KEY,
                'stripe_payment_id_missing'
            );
        }
        
        try {
            $response =
                $this->gateway->cancel(
                    $providerpaymentid
                );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Stripe payment cancellation failed.',
                self::KEY,
                'stripe_cancellation_failed',
                [
                    'providerpaymentid' =>
                        $providerpaymentid,
                ],
                $exception
            );
        }

        return $this->map_response(
            $this->resolve_request_reference(
                $response,
                $providerpaymentid
            ),
            $response
        );
    }

    public function refund(
        CommercePaymentRefundRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentRefundResult {
        if (!$this->is_available()) {
            throw new CommercePaymentProviderException(
                'Stripe is not available for Commerce refund.',
                self::KEY,
                'stripe_refund_provider_unavailable'
            );
        }

        $response = $this->gateway->refund(
            new StripeRefundRequest(
                $request->get_payment_reference(),
                $request->get_provider_payment_id(),
                $request->get_currency(),
                $request->get_amount_minor(),
                $request->get_reason(),
                array_merge(
                    $request->get_metadata(),
                    ['idempotency_key' => $context->get_idempotency_key()]
                )
            )
        );

        $status = match ($response->get_status()) {
            'succeeded' => CommercePaymentRefundResult::STATUS_SUCCEEDED,
            'failed' => CommercePaymentRefundResult::STATUS_FAILED,
            default => CommercePaymentRefundResult::STATUS_PENDING,
        };

        return new CommercePaymentRefundResult(
            self::KEY,
            $response->get_refund_id(),
            $status,
            $response->get_currency(),
            $response->get_amount_minor(),
            array_merge(
                $response->get_metadata(),
                [
                    'paymentreference' => $request->get_payment_reference(),
                    'providerpaymentid' => $request->get_provider_payment_id(),
                ]
            )
        );
    }

    public function list_refunds(
        string $providerpaymentid,
        string $currency,
        CommercePaymentProviderContext $context
    ): array {
        return array_map(
            static function(StripeRefundResponse $response): CommercePaymentRefundResult {
                $status = match ($response->get_status()) {
                    'succeeded' => CommercePaymentRefundResult::STATUS_SUCCEEDED,
                    'failed' => CommercePaymentRefundResult::STATUS_FAILED,
                    default => CommercePaymentRefundResult::STATUS_PENDING,
                };

                return new CommercePaymentRefundResult(
                    self::KEY,
                    $response->get_refund_id(),
                    $status,
                    $response->get_currency(),
                    $response->get_amount_minor(),
                    array_merge(
                        $response->get_metadata(),
                        ['historical_import' => true]
                    )
                );
            },
            $this->gateway->list_refunds(
                $providerpaymentid,
                $currency
            )
        );
    }

    private function build_gateway_request(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): StripeGatewayRequest {
        $lines = array_map(
            static fn($line): array => [
                'reference' =>
                    $line->get_reference(),

                'description' =>
                    $line->get_description(),

                'quantity' =>
                    $line->get_quantity(),

                'unitamountminor' =>
                    $line->get_unit_amount_minor(),

                'currency' =>
                    $line->get_currency(),
            ],
            $request->get_lines()
        );

        return new StripeGatewayRequest(
            $request->get_reference(),
            $request->get_amount_minor(),
            $request->get_currency(),
            $request->get_customer()->get_email(),
            $lines,
            (string)$request->get_return_url(),
            (string)$request->get_cancel_url(),
            $context->get_idempotency_key(),
            $this->build_provider_metadata(
                $request,
                $context
            )
        );
    }

    private function build_provider_metadata(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): array {
        $metadata =
            $request->get_metadata();

        $metadata = array_merge(
            $metadata,
            [
                'commerce_reference' =>
                    $request->get_reference(),

                'userid' =>
                    $request
                        ->get_customer()
                        ->get_user_id(),

                'customer_email' =>
                    $request
                        ->get_customer()
                        ->get_email(),

                'environment' =>
                    $context->is_live()
                        ? 'live'
                        : 'test',

                'commerce_payment_method' =>
                    $request->get_preferred_payment_method(),
            ]
        );

        return array_filter(
            $metadata,
            static fn(mixed $value): bool =>
                $value !== null
                && $value !== ''
        );
    }

    private function map_response(
        string $requestreference,
        StripeGatewayResponse $response
    ): CommercePaymentResult {
        $status = $this->map_status(
            $response->get_status()
        );

        $metadata = array_merge(
            $response->get_metadata(),
            [
                'stripe_status' =>
                    $response->get_status(),
            ]
        );

        if (
            $status ===
                CommercePaymentStatus::REQUIRES_ACTION
        ) {
            $embedded =
                !empty(
                    $response->get_metadata()['embedded']
                    ?? false
                );

            if ($embedded) {
                $responsemetadata =
                    $response->get_metadata();

                foreach (
                    [
                        'client_secret',
                        'publishable_key',
                        'return_url',
                    ]
                    as $required
                ) {
                    if (
                        trim(
                            (string)(
                                $responsemetadata[$required]
                                ?? ''
                            )
                        ) === ''
                    ) {
                        throw new CommercePaymentProviderException(
                            'Stripe embedded payment data is incomplete.',
                            self::KEY,
                            'stripe_embedded_action_incomplete',
                            ['missing' => $required]
                        );
                    }
                }

                return CommercePaymentResult::requires_action(
                    $requestreference,
                    self::KEY,
                    $response->get_payment_id(),
                    CommercePaymentAction::embedded(
                        [
                            'client_secret' =>
                                $responsemetadata['client_secret'],
                            'publishable_key' =>
                                $responsemetadata['publishable_key'],
                            'return_url' =>
                                $responsemetadata['return_url'],
                            'payment_intent' =>
                                $response->get_payment_id(),
                        ],
                        [
                            'method' => 'card',
                            'provider' => self::KEY,
                        ]
                    ),
                    $metadata
                );
            }

            $checkouturl =
                $response->get_checkout_url();

            if ($checkouturl === null) {
                throw new CommercePaymentProviderException(
                    'Stripe returned an actionable payment without a checkout URL.',
                    self::KEY,
                    'stripe_checkout_url_missing'
                );
            }

            return CommercePaymentResult::requires_action(
                $requestreference,
                self::KEY,
                $response->get_payment_id(),
                CommercePaymentAction::redirect(
                    $checkouturl
                ),
                $metadata
            );
        }

        if ($status === CommercePaymentStatus::FAILED) {
            return CommercePaymentResult::failed(
                $requestreference,
                self::KEY,
                $response->get_error_code()
                    ?? 'stripe_payment_failed',
                $response->get_error_message()
                    ?? 'Stripe reported a payment failure.',
                $response->get_payment_id(),
                $metadata
            );
        }

        return new CommercePaymentResult(
            $requestreference,
            self::KEY,
            $status,
            $response->get_payment_id(),
            null,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    private function map_status(
        string $status
    ): string {
        return match (
            strtolower(trim($status))
        ) {
            StripeGatewayResponse::STATUS_CREATED,
            StripeGatewayResponse::STATUS_OPEN =>
                CommercePaymentStatus::REQUIRES_ACTION,

            StripeGatewayResponse::STATUS_PENDING =>
                CommercePaymentStatus::PENDING,

            StripeGatewayResponse::STATUS_COMPLETE,
            StripeGatewayResponse::STATUS_PAID =>
                CommercePaymentStatus::SUCCEEDED,

            StripeGatewayResponse::STATUS_FAILED =>
                CommercePaymentStatus::FAILED,

            StripeGatewayResponse::STATUS_CANCELLED =>
                CommercePaymentStatus::CANCELLED,

            StripeGatewayResponse::STATUS_EXPIRED =>
                CommercePaymentStatus::EXPIRED,

            StripeGatewayResponse::STATUS_REFUNDED =>
                CommercePaymentStatus::REFUNDED,

            StripeGatewayResponse::STATUS_PARTIALLY_REFUNDED =>
                CommercePaymentStatus::PARTIALLY_REFUNDED,

            default =>
                CommercePaymentStatus::PENDING,
        };
    }

    private function resolve_request_reference(
        StripeGatewayResponse $response,
        string $fallback
    ): string {
        $reference =
            $response->get_metadata()[
                'commerce_reference'
            ] ?? null;

        if (
            is_string($reference)
            && trim($reference) !== ''
        ) {
            return trim($reference);
        }

        return 'stripe-payment:' . $fallback;
    }
}