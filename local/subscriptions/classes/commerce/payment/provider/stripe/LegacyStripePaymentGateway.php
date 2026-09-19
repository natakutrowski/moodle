<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestAdapter;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\RefundGatewayInterface;
use local_subscriptions\payment\RefundHistoryGatewayInterface;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\stripe\StripeConfiguration;
use local_subscriptions\payment\dto\CheckoutInitResult;

/**
 * Transitional bridge between Commerce and the existing Stripe gateway.
 *
 * This class must remain thin. Stripe API calls, SDK configuration and
 * provider-specific payload construction remain owned by the Legacy gateway.
 *
 * To be removed after the payment infrastructure has been fully migrated
 * under the Commerce namespace.
 */
final class LegacyStripePaymentGateway
    implements StripePaymentGateway {

    private PaymentGatewayInterface $legacygateway;

    public function __construct(
        private readonly LegacyPaymentRequestAdapter $requestadapter,
        ?PaymentGatewayInterface $legacygateway = null
    ) {
        $this->legacygateway = $legacygateway
            ?? PaymentGatewayFactory::for(
                Provider::STRIPE
            );
    }

    public function is_configured(): bool {
        $environment = Provider::env(
            Provider::STRIPE
        );

        $environment = $environment === 'live'
            ? 'live'
            : 'test';

        $secret = get_config(
            'local_subscriptions',
            'stripe_' . $environment . '_secret'
        );

        return trim((string)$secret) !== '';
    }

    public function create_checkout_session(
        StripeGatewayRequest $request
    ): StripeGatewayResponse {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $request->get_metadata(),
                Provider::STRIPE
            );

        $legacyrequest =
            $this->requestadapter->load_and_validate(
                $context,
                $request->get_amount_minor(),
                $request->get_currency(),
                $request->get_customer_email(),
                $this->resolve_user_id(
                    $request->get_metadata()
                )
            );

        $options = $this->build_legacy_options(
            $request,
            $context,
            $legacyrequest
        );

        try {
            $result =
                $this->legacygateway
                    ->create_checkout_session(
                        $legacyrequest,
                        $options
                    );
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway failed to create a checkout session.',
                Provider::STRIPE,
                'legacy_stripe_checkout_creation_failed',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),

                    'legacy_payment_request_table' =>
                        $context->get_payment_request_table(),
                ],
                $exception
            );
        }

        return $this->map_checkout_result(
            $request,
            $context,
            $result
        );
    }

    public function create_payment_intent(
        StripeGatewayRequest $request
    ): StripeGatewayResponse {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $request->get_metadata(),
                Provider::STRIPE
            );

        $legacyrequest =
            $this->requestadapter->load_and_validate(
                $context,
                $request->get_amount_minor(),
                $request->get_currency(),
                $request->get_customer_email(),
                $this->resolve_user_id(
                    $request->get_metadata()
                )
            );

        if ($context->get_mode() !== 'payment') {
            // Recurring legacy flows continue through Checkout Session for H2.
            return $this->create_checkout_session(
                $request
            );
        }

        $this->ensure_stripe_sdk();
        $configuration = StripeConfiguration::get();

        if (trim((string)$configuration['secret_key']) === '') {
            throw new CommercePaymentProviderException(
                'Stripe secret key is missing for embedded payment.',
                Provider::STRIPE,
                'stripe_embedded_secret_missing'
            );
        }

        \Stripe\Stripe::setApiKey(
            $configuration['secret_key']
        );

        $requestedembeddedmethods =
            $request->get_metadata()[
                'embedded_payment_methods'
            ]
            ?? [];

        $selectedpaymentmethod =
            trim(
                strtolower(
                    (string)(
                        $request->get_metadata()[
                            'payment_method'
                        ]
                        ?? 'card'
                    )
                )
            ) ?: 'card';

        // H12.9-A6.5.1: Express Checkout Elements is initialized in deferred
        // Intent mode with the complete authorized Stripe method pool. Stripe
        // requires the PaymentIntent created at confirmation time to expose
        // exactly that same pool. Restricting the Intent to the method selected
        // by the customer (for example ['card'] for Apple Pay) makes
        // confirmPayment() fail before the card is even presented to the bank.
        $paymentmethodtypes = [];

        foreach ($requestedembeddedmethods as $embeddedmethod) {
            $embeddedmethod =
                strtolower(
                    trim((string)$embeddedmethod)
                );

            if (
                in_array(
                    $embeddedmethod,
                    [
                        'card',
                        'apple_pay',
                        'google_pay',
                    ],
                    true
                )
            ) {
                $paymentmethodtypes[] = 'card';
                continue;
            }

            if ($embeddedmethod === 'link') {
                // Link is exposed together with the card rail.
                $paymentmethodtypes[] = 'card';
                $paymentmethodtypes[] = 'link';
                continue;
            }

            if ($embeddedmethod === 'klarna') {
                $paymentmethodtypes[] = 'klarna';
            }
        }

        $paymentmethodtypes =
            array_values(
                array_unique(
                    $paymentmethodtypes
                )
            );

        // Defensive fallback for older/non-Express callers that do not yet
        // provide embedded_payment_methods.
        if ($paymentmethodtypes === []) {
            $paymentmethodtypes =
                match ($selectedpaymentmethod) {
                    'link' => ['card', 'link'],
                    'klarna' => ['klarna'],
                    default => ['card'],
                };
        }

        $metadata = array_merge(
            $this->build_stripe_metadata(
                $request,
                $context
            ),
            [
                'payment_request_id' =>
                    (string)$context->get_payment_request_id(),
                'stripe_profile' =>
                    $configuration['profile'],
                'commerce_payment_method' =>
                    trim(
                        (string)(
                            $request->get_metadata()[
                                'payment_method'
                            ]
                            ?? 'card'
                        )
                    ) ?: 'card',
                'commerce_execution_mode' =>
                    'campus_embedded',
            ]
        );

        try {
            $intent = \Stripe\PaymentIntent::create(
                [
                    'amount' =>
                        $request->get_amount_minor(),
                    'currency' =>
                        strtolower(
                            $request->get_currency()
                        ),
                    'payment_method_types' =>
                        $paymentmethodtypes,
                    'receipt_email' =>
                        $request->get_customer_email(),
                    'description' =>
                        $this->build_product_name(
                            $request
                        ),
                    'metadata' =>
                        $metadata,
                ],
                [
                    'idempotency_key' =>
                        $request->get_idempotency_key(),
                ]
            );
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'Stripe embedded PaymentIntent creation failed.',
                Provider::STRIPE,
                'stripe_embedded_intent_creation_failed',
                [
                    'commerce_reference' =>
                        $request->get_reference(),
                ],
                $exception
            );
        }

        $clientsecret = trim(
            (string)($intent->client_secret ?? '')
        );

        if ($clientsecret === '') {
            throw new CommercePaymentProviderException(
                'Stripe PaymentIntent returned no client secret.',
                Provider::STRIPE,
                'stripe_embedded_client_secret_missing'
            );
        }

        return new StripeGatewayResponse(
            (string)$intent->id,
            StripeGatewayResponse::STATUS_OPEN,
            null,
            null,
            null,
            array_merge(
                $metadata,
                [
                    'embedded' => true,
                    'client_secret' =>
                        $clientsecret,
                    'publishable_key' =>
                        (string)$configuration[
                            'publishable_key'
                        ],
                    'return_url' =>
                        $request->get_success_url(),
                    'payment_intent' =>
                        (string)$intent->id,
                ]
            )
        );
    }

    private function ensure_stripe_sdk(): void {
        global $CFG;

        if (class_exists(\Stripe\PaymentIntent::class)) {
            return;
        }

        $autoload =
            $CFG->dirroot
            . '/local/subscriptions/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException(
                'Stripe SDK autoload not found.'
            );
        }

        require_once $autoload;
    }

    public function retrieve(
        string $paymentid
    ): StripeGatewayResponse {
        throw new CommercePaymentProviderException(
            'Stripe payment retrieval is not exposed by the Legacy gateway.',
            Provider::STRIPE,
            'legacy_stripe_retrieval_not_supported',
            [
                'providerpaymentid' =>
                    trim($paymentid),
            ]
        );
    }

    public function cancel(
        string $paymentid
    ): StripeGatewayResponse {
        throw new CommercePaymentProviderException(
            'Stripe payment cancellation is not exposed by the Legacy gateway.',
            Provider::STRIPE,
            'legacy_stripe_cancellation_not_supported',
            [
                'providerpaymentid' =>
                    trim($paymentid),
            ]
        );
    }

    public function refund(
        StripeRefundRequest $request
    ): StripeRefundResponse {
        if (!$this->legacygateway instanceof RefundGatewayInterface) {
            throw new CommercePaymentProviderException(
                'Stripe refund is not exposed by the configured Legacy gateway.',
                Provider::STRIPE,
                'legacy_stripe_refund_not_supported',
                ['providerpaymentid' => $request->get_provider_payment_id()]
            );
        }

        try {
            $metadata = $request->get_metadata();
            $result = $this->legacygateway->refund_payment(
                $request->get_provider_payment_id(),
                $request->get_amount_minor(),
                $request->get_currency(),
                [
                    'payment_reference' => $request->get_payment_reference(),
                    'purchase_reference' =>
                        trim((string)($metadata['purchase_reference'] ?? '')),
                    'reason' => $request->get_reason(),
                    'idempotency_key' =>
                        trim((string)($metadata['idempotency_key'] ?? '')),
                ]
            );
        } catch (CommercePaymentProviderException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway failed to refund the payment.',
                Provider::STRIPE,
                'legacy_stripe_refund_failed',
                [
                    'providerpaymentid' => $request->get_provider_payment_id(),
                    'paymentreference' => $request->get_payment_reference(),
                    'currency' => $request->get_currency(),
                    'amountminor' => $request->get_amount_minor(),
                ],
                $exception
            );
        }

        return new StripeRefundResponse(
            $result->providerrefundid,
            $result->status,
            $result->currency,
            $result->amountminor,
            $result->metadata
        );
    }

    public function list_refunds(
        string $providerpaymentid,
        string $currency
    ): array {
        if (!$this->legacygateway instanceof RefundHistoryGatewayInterface) {
            return [];
        }

        $results = $this->legacygateway->list_payment_refunds(
            $providerpaymentid,
            $currency
        );

        return array_map(
            static fn($result): StripeRefundResponse =>
                new StripeRefundResponse(
                    $result->providerrefundid,
                    $result->status,
                    $result->currency,
                    $result->amountminor,
                    $result->metadata
                ),
            $results
        );
    }

    private function build_legacy_options(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context,
        \stdClass $legacyrequest
    ): array {
        $metadata = $request->get_metadata();

        $mode = $context->get_mode();

        if (
            !in_array(
                $mode,
                [
                    'payment',
                    'subscription',
                ],
                true
            )
        ) {
            throw new CommercePaymentProviderException(
                'The requested Legacy Stripe checkout mode is invalid.',
                Provider::STRIPE,
                'legacy_stripe_mode_invalid',
                [
                    'mode' =>
                        $mode,

                    'commerce_reference' =>
                        $request->get_reference(),
                ]
            );
        }

        $options = [
            'mode' =>
                $mode,

            /*
             * Commerce always uses the amount already locked and validated
             * against the persisted Legacy Payment Request.
             */
            'use_locked_amount' =>
                true,

            'amount_minor' =>
                $request->get_amount_minor(),

            'product_name' =>
                $this->build_product_name(
                    $request
                ),

            'success_url' =>
                $request->get_success_url(),

            'cancel_url' =>
                $request->get_cancel_url(),

            'email' =>
                $legacyrequest->email
                    ?? $request->get_customer_email(),

            'firstname' =>
                $legacyrequest->firstname
                    ?? null,

            'lastname' =>
                $legacyrequest->lastname
                    ?? null,

            'metadata' =>
                $this->build_stripe_metadata(
                    $request,
                    $context
                ),

            /*
             * Kept for the later Stripe idempotency hardening.
             * The current Legacy StripeGateway does not consume it yet.
             */
            'idempotency_key' =>
                $request->get_idempotency_key(),

            'preferred_payment_method' =>
                trim(
                    (string)(
                        $metadata['commerce_payment_method']
                        ?? ''
                    )
                ),
        ];

        if ($mode === 'subscription') {
            $stripepriceid =
                $context->get_stripe_price_id();

            if ($stripepriceid !== null) {
                $options['price_map'] = [
                    'stripe_price_id' =>
                        $stripepriceid,
                ];
            }
        }

        if (
            isset($metadata['legacy_operation'])
            && is_scalar(
                $metadata['legacy_operation']
            )
        ) {
            $options['operation'] = trim(
                (string)$metadata[
                    'legacy_operation'
                ]
            );
        }

        if (
            isset($metadata['legacy_ref_subscription_id'])
            && (
                is_int(
                    $metadata[
                        'legacy_ref_subscription_id'
                    ]
                )
                || (
                    is_string(
                        $metadata[
                            'legacy_ref_subscription_id'
                        ]
                    )
                    && ctype_digit(
                        $metadata[
                            'legacy_ref_subscription_id'
                        ]
                    )
                )
            )
        ) {
            $options['ref_sub_id'] = (int)$metadata[
                'legacy_ref_subscription_id'
            ];
        }

        return $options;
    }

    private function build_stripe_metadata(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context
    ): array {
        $source = $request->get_metadata();

        $metadata = [
            'commerce_reference' =>
                $request->get_reference(),

            'commerce_payment_id' =>
                $this->required_native_identity(
                    $source,
                    'commerce_payment_id'
                ),

            'commerce_purchase_uuid' =>
                $this->required_native_identity(
                    $source,
                    'commerce_purchase_uuid'
                ),

            'payment_context' =>
                'commerce',
        ];

        foreach (
            [
                'userid' =>
                    'userid',

                'legacy_plan_id' =>
                    'planid',

                'legacy_product_id' =>
                    'productid',

                'legacy_operation' =>
                    'operation',

                'legacy_language' =>
                    'uilang',

                'legacy_slug' =>
                    'slug',
            ] as $sourcekey => $targetkey
        ) {
            if (!array_key_exists($sourcekey, $source)) {
                continue;
            }

            $value = $source[$sourcekey];

            if (!is_scalar($value) || trim((string)$value) === '') {
                continue;
            }

            $metadata[$targetkey] =
                (string)$value;
        }

        return $metadata;
    }

    private function required_native_identity(
        array $metadata,
        string $key
    ): string {
        $value = $metadata[$key] ?? null;

        if (!is_scalar($value) || trim((string)$value) === '') {
            throw new CommercePaymentProviderException(
                'The Commerce Stripe request is missing its Native payment identity.',
                Provider::STRIPE,
                'commerce_stripe_native_identity_missing',
                [
                    'missingkey' => $key,
                    'commerce_reference' => $metadata['commerce_reference'] ?? null,
                ]
            );
        }

        return trim((string)$value);
    }

    private function build_product_name(
        StripeGatewayRequest $request
    ): string {
        $descriptions = [];

        foreach ($request->get_lines() as $line) {
            if (!is_array($line)) {
                continue;
            }

            $description = trim(
                (string)(
                    $line['description']
                    ?? ''
                )
            );

            if ($description !== '') {
                $descriptions[] =
                    $description;
            }
        }

        $descriptions = array_values(
            array_unique(
                $descriptions
            )
        );

        if ($descriptions === []) {
            return get_string(
                'stripe:productname',
                'local_subscriptions',
                'CampusFR'
            );
        }

        return \core_text::substr(
            implode(
                ' + ',
                $descriptions
            ),
            0,
            255
        );
    }

    private function map_checkout_result(
        StripeGatewayRequest $request,
        LegacyPaymentRequestContext $context,
        CheckoutInitResult $result
    ): StripeGatewayResponse {
        $redirecturl = trim(
            $result->redirect_url
        );

        $sessionid = trim(
            (string)(
                $result->provider_session_id
                ?? ''
            )
        );

        if ($redirecturl === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway returned no checkout URL.',
                Provider::STRIPE,
                'legacy_stripe_checkout_url_missing',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),
                ]
            );
        }

        if ($sessionid === '') {
            throw new CommercePaymentProviderException(
                'The Legacy Stripe gateway returned no session identifier.',
                Provider::STRIPE,
                'legacy_stripe_session_id_missing',
                [
                    'commerce_reference' =>
                        $request->get_reference(),

                    'legacy_payment_request_id' =>
                        $context->get_payment_request_id(),
                ]
            );
        }

        return new StripeGatewayResponse(
            $sessionid,
            StripeGatewayResponse::STATUS_OPEN,
            $redirecturl,
            null,
            null,
            [
                'commerce_reference' =>
                    $request->get_reference(),

                'commerce_payment_id' =>
                    $this->required_native_identity(
                        $request->get_metadata(),
                        'commerce_payment_id'
                    ),

                'commerce_purchase_uuid' =>
                    $this->required_native_identity(
                        $request->get_metadata(),
                        'commerce_purchase_uuid'
                    ),

                'stripe_session_id' =>
                    $sessionid,
            ]
        );
    }

    private function resolve_user_id(
        array $metadata
    ): ?int {
        $value = $metadata['userid']
            ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (
            is_int($value)
            || (
                is_string($value)
                && ctype_digit($value)
            )
        ) {
            $value = (int)$value;

            return $value > 0
                ? $value
                : null;
        }

        throw new CommercePaymentProviderException(
            'The Commerce Stripe metadata contains an invalid user identifier.',
            Provider::STRIPE,
            'legacy_stripe_user_id_invalid',
            [
                'userid' =>
                    $value,
            ]
        );
    }
}