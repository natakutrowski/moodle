<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

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
 * Commerce adapter for the existing Alfa payment integration.
 */
final class AlfaCommercePaymentProvider
    implements CommercePaymentProvider, CommerceRefundCapablePaymentProvider, CommerceRefundHistoryCapablePaymentProvider {

    public const KEY = 'alfa';

    public function __construct(
        private readonly AlfaPaymentGateway $gateway,
        private readonly AlfaPaymentProviderConfiguration $configuration
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

            // Alfa redirects the customer to its hosted payment form.
            true,

            // Payment cancellation is not exposed by the Legacy gateway.
            false,

            // Status lookup exists internally but is not exposed through
            // PaymentGatewayInterface.
            false,

            // Certified through CommerceRefundCapablePaymentProvider.
            true,

            // Several Commerce lines can be aggregated into one Alfa payment.
            true,

            [
                'checkout_mode' =>
                    'bank_form',

                'provider' =>
                    self::KEY,

                'confirmation_mode' =>
                    'legacy_return_or_webhook',

                'bridge' =>
                    'legacy',
            ],
            array_values(
                array_filter(
                    [
                        CommercePaymentMethod::CARD,
                        $this->gateway instanceof AlfaPayPaymentGateway
                        && $this->gateway->is_alfa_pay_configured()
                            ? CommercePaymentMethod::ALFA_PAY
                            : null,
                        $this->gateway instanceof AlfaSbpPaymentGateway
                        && $this->gateway->is_sbp_configured()
                            ? CommercePaymentMethod::SBP
                            : null,
                    ]
                )
            )
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
                'alfa_disabled',
                'The Alfa Commerce provider is disabled.'
            );
        }

        if (!$this->gateway->is_configured()) {
            $result->add_error(
                'alfa_not_configured',
                'The Alfa gateway is not configured.'
            );
        }

        if (!$request->requires_payment()) {
            $result->add_error(
                'alfa_free_payment_not_supported',
                'Alfa must not initialize zero-amount payments.'
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
                'alfa_currency_not_supported',
                'Alfa does not support the requested currency.',
                [
                    'currency' =>
                        $request->get_currency(),
                ]
            );
        }

        if ($request->get_return_url() === null) {
            $result->add_error(
                'alfa_return_url_missing',
                'Alfa requires a payment return URL.'
            );
        }

        if ($request->get_cancel_url() === null) {
            $result->add_error(
                'alfa_fail_url_missing',
                'Alfa requires a payment failure URL.'
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
                'The Alfa payment request is invalid.',
                self::KEY,
                'alfa_validation_failed',
                [
                    'validation' =>
                        $validation->to_array(),
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

            $preferredmethod =
                $request->get_preferred_payment_method();

            if (
                $preferredmethod === CommercePaymentMethod::SBP
                && $this->gateway instanceof AlfaSbpPaymentGateway
            ) {
                $response =
                    $this->gateway->register_sbp(
                        $gatewayrequest
                    );
            } else if (
                $preferredmethod === CommercePaymentMethod::ALFA_PAY
                && $this->gateway instanceof AlfaPayPaymentGateway
            ) {
                $response =
                    $this->gateway->register_alfa_pay(
                        $gatewayrequest
                    );
            } else if (
                $executionmode
                    === CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED
                && $preferredmethod
                    === CommercePaymentMethod::CARD
                && AlfaIframeConfiguration::is_enabled()
            ) {
                // H12.5.2: iframe uses the normal register.do API flow.
                // Environment and API credentials therefore remain resolved by
                // the existing Alfa gateway (TEST/LIVE); no OpenID widget token
                // is required for this execution path.
                $response =
                    $this->gateway->register(
                        $gatewayrequest
                    );
            } else if (
                $executionmode
                    === CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED
                && $preferredmethod
                    === CommercePaymentMethod::CARD
                && AlfaWidgetConfiguration::is_available()
            ) {
                $response =
                    $this->gateway->prepare_widget(
                        $gatewayrequest
                    );
            } else {
                $response =
                    $this->gateway->register(
                        $gatewayrequest
                    );
            }
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment registration failed.',
                self::KEY,
                'alfa_initialization_failed',
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
                'The Alfa order identifier cannot be empty.',
                self::KEY,
                'alfa_order_id_missing'
            );
        }

        try {
            $response = $this->gateway->retrieve(
                $providerpaymentid
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment retrieval failed.',
                self::KEY,
                'alfa_retrieval_failed',
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
                'The Alfa order identifier cannot be empty.',
                self::KEY,
                'alfa_order_id_missing'
            );
        }

        try {
            $response = $this->gateway->cancel(
                $providerpaymentid
            );
        } catch (
            CommercePaymentProviderException $exception
        ) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Alfa payment cancellation failed.',
                self::KEY,
                'alfa_cancellation_failed',
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
                'Alfa is not available for Commerce refund.',
                self::KEY,
                'alfa_refund_provider_unavailable'
            );
        }

        if ($request->get_currency() !== 'RUB') {
            throw new CommercePaymentProviderException(
                'The current Alfa Commerce integration refunds RUB only.',
                self::KEY,
                'alfa_refund_currency_not_supported',
                ['currency' => $request->get_currency()]
            );
        }

        $response = $this->gateway->refund(
            new AlfaRefundRequest(
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
            static function(
                AlfaRefundResponse $response
            ): CommercePaymentRefundResult {
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
            $this->gateway->list_refunds($providerpaymentid, $currency)
        );
    }

    private function build_gateway_request(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): AlfaGatewayRequest {
        $metadata = array_merge(
            $request->get_metadata(),
            [
                /*
                 * The following values are authoritative and cannot be
                 * overridden by arbitrary request metadata.
                 */
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

                'alfa_iframe_requested' =>
                    $request->get_preferred_payment_method()
                        === CommercePaymentMethod::CARD
                    && AlfaIframeConfiguration::is_enabled(),
            ]
        );

        $metadata = array_filter(
            $metadata,
            static fn(mixed $value): bool =>
                $value !== null
                && $value !== ''
        );

        $returnurl =
            (string)$request->get_return_url();

        $failurl =
            (string)$request->get_cancel_url();

        if (!empty($metadata['alfa_iframe_requested'])) {
            // H12.5.6: Alfa first returns to a tiny same-origin bridge.
            // The bridge paints the parent splash immediately, then keeps
            // authoritative reconciliation inside payment/return.php.
            $returnurl = (
                new \moodle_url(
                    '/local/subscriptions/payment/embedded_return_bridge.php',
                    [
                        'target' =>
                            (new \moodle_url(
                                $returnurl,
                                ['embedded' => 1]
                            ))->out(false),
                    ]
                )
            )->out(false);
        }

        return new AlfaGatewayRequest(
            $this->build_order_number(
                $request->get_reference()
            ),
            $request->get_amount_minor(),
            $request->get_currency(),
            $this->build_description($request),
            $request
                ->get_customer()
                ->get_email(),
            $returnurl,
            $failurl,
            $context->get_idempotency_key(),
            $metadata
        );
    }

    private function build_order_number(
        string $reference
    ): string {
        $normalised = preg_replace(
            '/[^a-zA-Z0-9_-]+/',
            '-',
            $reference
        );

        $normalised = trim(
            (string)$normalised,
            '-_'
        );

        if ($normalised === '') {
            $normalised = 'commerce';
        }

        return substr(
            $normalised,
            0,
            80
        );
    }

    private function build_description(
        CommercePaymentRequest $request
    ): string {
        $descriptions = array_map(
            static fn($line): string =>
                $line->get_description(),
            $request->get_lines()
        );

        $description = implode(
            ' + ',
            $descriptions
        );

        return \core_text::substr(
            $description,
            0,
            255
        );
    }

    private function map_response(
        string $requestreference,
        AlfaGatewayResponse $response
    ): CommercePaymentResult {
        $status = $this->map_status(
            $response->get_status()
        );

        $metadata = array_merge(
            $response->get_metadata(),
            [
                'alfa_status' =>
                    $response->get_status(),
            ]
        );

        if (
            $status ===
                CommercePaymentStatus::REQUIRES_ACTION
        ) {
            $responsemetadata =
                $response->get_metadata();

            if (
                !empty($responsemetadata['embedded'] ?? false)
                && ($responsemetadata['embedded_type'] ?? '') === 'alfa_sbp'
            ) {
                $required = [
                    'sbp_qr_id',
                    'sbp_qr_status',
                    'sbp_payload',
                    'return_url',
                    'commerce_payment_id',
                    'commerce_purchase_uuid',
                ];

                foreach ($required as $key) {
                    if (trim((string)($responsemetadata[$key] ?? '')) === '') {
                        throw new CommercePaymentProviderException(
                            'Alfa SBP action is incomplete.',
                            self::KEY,
                            'alfa_sbp_action_incomplete',
                            ['missing' => $key]
                        );
                    }
                }

                return CommercePaymentResult::requires_action(
                    $requestreference,
                    self::KEY,
                    $response->get_order_id(),
                    CommercePaymentAction::embedded(
                        [
                            'embedded_type' => 'alfa_sbp',
                            'qr_id' => $responsemetadata['sbp_qr_id'],
                            'qr_status' => $responsemetadata['sbp_qr_status'],
                            'payload' => $responsemetadata['sbp_payload'],
                            'rendered_qr' => $responsemetadata['sbp_rendered_qr'] ?? '',
                            'return_url' => $responsemetadata['return_url'],
                            'status_url' =>
                                $this->build_sbp_status_url(
                                    $responsemetadata
                                ),
                        ],
                        [
                            'method' => CommercePaymentMethod::SBP,
                            'provider' => self::KEY,
                            'surface' => 'alfa_sbp',
                        ]
                    ),
                    $metadata
                );
            }

            if (
                !empty(
                    $responsemetadata['alfa_iframe_requested']
                    ?? false
                )
            ) {
                $formurl = $response->get_form_url();

                if ($formurl === null) {
                    throw new CommercePaymentProviderException(
                        'Alfa iframe action is missing formUrl.',
                        self::KEY,
                        'alfa_iframe_form_url_missing'
                    );
                }

                return CommercePaymentResult::requires_action(
                    $requestreference,
                    self::KEY,
                    $response->get_order_id(),
                    CommercePaymentAction::embedded(
                        [
                            'embedded_type' => 'alfa_iframe',
                            'form_url' => $formurl,
                            'return_url' =>
                                (string)($responsemetadata['return_url'] ?? ''),
                            'fail_url' =>
                                (string)($responsemetadata['fail_url'] ?? ''),
                        ],
                        [
                            'method' => CommercePaymentMethod::CARD,
                            'provider' => self::KEY,
                            'surface' => 'alfa_iframe',
                        ]
                    ),
                    $metadata
                );
            }

            if (
                !empty(
                    $responsemetadata['embedded']
                    ?? false
                )
                && (
                    $responsemetadata['embedded_type']
                    ?? ''
                ) === 'alfa_widget'
            ) {
                $required = [
                    'widget_token',
                    'widget_script_url',
                    'widget_gateway',
                    'widget_amount_minor',
                    'alfa_order_number',
                    'return_url',
                    'fail_url',
                ];

                foreach ($required as $key) {
                    if (
                        trim(
                            (string)(
                                $responsemetadata[$key]
                                ?? ''
                            )
                        ) === ''
                    ) {
                        throw new CommercePaymentProviderException(
                            'Alfa widget action is incomplete.',
                            self::KEY,
                            'alfa_widget_action_incomplete',
                            ['missing' => $key]
                        );
                    }
                }

                return CommercePaymentResult::requires_action(
                    $requestreference,
                    self::KEY,
                    $response->get_order_id(),
                    CommercePaymentAction::embedded(
                        [
                            'embedded_type' => 'alfa_widget',
                            'token' =>
                                $responsemetadata['widget_token'],
                            'script_url' =>
                                $responsemetadata['widget_script_url'],
                            'gateway' =>
                                $responsemetadata['widget_gateway'],
                            'amount_minor' =>
                                $responsemetadata['widget_amount_minor'],
                            'amount_format' =>
                                $responsemetadata['widget_amount_format']
                                ?? 'kopeyki',
                            'version' =>
                                $responsemetadata['widget_version']
                                ?? '1.0',
                            'stages' =>
                                $responsemetadata['widget_stages']
                                ?? '1',
                            'language' =>
                                $responsemetadata['widget_language']
                                ?? 'ru',
                            'order_number' =>
                                $responsemetadata['alfa_order_number'],
                            'description' =>
                                $responsemetadata['widget_description']
                                ?? '',
                            'return_url' =>
                                $responsemetadata['return_url'],
                            'fail_url' =>
                                $responsemetadata['fail_url'],
                        ],
                        [
                            'method' => 'card',
                            'provider' => self::KEY,
                            'surface' => 'alfa_widget',
                        ]
                    ),
                    $metadata
                );
            }

            $formurl =
                $response->get_form_url();

            if ($formurl === null) {
                throw new CommercePaymentProviderException(
                    'Alfa returned a registered order without a payment form URL.',
                    self::KEY,
                    'alfa_form_url_missing'
                );
            }

            return CommercePaymentResult::requires_action(
                $requestreference,
                self::KEY,
                $response->get_order_id(),
                CommercePaymentAction::redirect(
                    $formurl
                ),
                $metadata
            );
        }

        if ($status === CommercePaymentStatus::FAILED) {
            return CommercePaymentResult::failed(
                $requestreference,
                self::KEY,
                $response->get_error_code()
                    ?? 'alfa_payment_failed',
                $response->get_error_message()
                    ?? 'Alfa reported a payment failure.',
                $response->get_order_id(),
                $metadata
            );
        }

        return new CommercePaymentResult(
            $requestreference,
            self::KEY,
            $status,
            $response->get_order_id(),
            null,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    private function build_sbp_status_url(
        array $metadata
    ): string {
        global $CFG;

        $paymentid =
            (int)(
                $metadata['commerce_payment_id']
                ?? 0
            );
        $purchaseuuid =
            trim(
                (string)(
                    $metadata['commerce_purchase_uuid']
                    ?? ''
                )
            );

        if (
            $paymentid <= 0
            || $purchaseuuid === ''
        ) {
            throw new CommercePaymentProviderException(
                'Alfa SBP status URL cannot be signed without native payment identity.',
                self::KEY,
                'alfa_sbp_status_identity_missing'
            );
        }

        $expires =
            time()
            + HOURSECS;

        $secret =
            (string)(
                $CFG->passwordsaltmain
                ?? $CFG->wwwroot
            );

        $signature =
            hash_hmac(
                'sha256',
                $paymentid
                    . '|'
                    . $purchaseuuid
                    . '|'
                    . $expires,
                $secret
            );

        return (
            new \moodle_url(
                '/local/subscriptions/payment/alfa_sbp_status.php',
                [
                    'paymentid' =>
                        $paymentid,
                    'purchaseuuid' =>
                        $purchaseuuid,
                    'expires' =>
                        $expires,
                    'signature' =>
                        $signature,
                ]
            )
        )->out(false);
    }

    private function map_status(
        string $status
    ): string {
        return match (
            strtolower(trim($status))
        ) {
            AlfaGatewayResponse::STATUS_REGISTERED =>
                CommercePaymentStatus::REQUIRES_ACTION,

            AlfaGatewayResponse::STATUS_PREAUTHORISED,
            AlfaGatewayResponse::STATUS_PENDING =>
                CommercePaymentStatus::PENDING,

            AlfaGatewayResponse::STATUS_PAID =>
                CommercePaymentStatus::SUCCEEDED,

            AlfaGatewayResponse::STATUS_DECLINED =>
                CommercePaymentStatus::FAILED,

            AlfaGatewayResponse::STATUS_CANCELLED =>
                CommercePaymentStatus::CANCELLED,

            AlfaGatewayResponse::STATUS_EXPIRED =>
                CommercePaymentStatus::EXPIRED,

            AlfaGatewayResponse::STATUS_REFUNDED =>
                CommercePaymentStatus::REFUNDED,

            AlfaGatewayResponse::STATUS_PARTIALLY_REFUNDED =>
                CommercePaymentStatus::PARTIALLY_REFUNDED,

            default =>
                CommercePaymentStatus::PENDING,
        };
    }

    private function resolve_request_reference(
        AlfaGatewayResponse $response,
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

        return 'alfa-payment:' . $fallback;
    }
}