<?php

namespace local_subscriptions\commerce\payment\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;

/**
 * Builds a Commerce request from an existing Legacy payment request.
 *
 * This factory performs no database write and no provider call.
 */
final class LegacyCommercePaymentRequestFactory {

    public function create(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $options = []
    ): CommercePaymentRequest {
        $paymentrequestid =
            (int)($paymentrequest->id ?? 0);

        if ($paymentrequestid <= 0) {
            throw new LegacyPaymentRequestMappingException(
                'A persisted Legacy payment request id is required.',
                'legacy_payment_request_id_missing'
            );
        }

        $provider =
            strtolower(
                trim(
                    (string)(
                        $paymentrequest->payment_provider
                        ?? ''
                    )
                )
            );

        if ($provider === '') {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request provider is missing.',
                'legacy_payment_provider_missing',
                [
                    'paymentrequestid' =>
                        $paymentrequestid,

                    'paymentrequesttable' =>
                        $paymentrequesttable,
                ]
            );
        }

        $paymentcontext =
            $this->resolve_payment_context(
                $paymentrequesttable
            );

        $ordernumberprefix =
            $this->resolve_order_number_prefix(
                $paymentrequesttable,
                $options
            );

        $language =
            $this->nullable_string(
                $options['language']
                ?? null
            );

        $mode =
            $this->nullable_string(
                $options['mode']
                ?? null
            );

        $stripepriceid =
            $this->nullable_string(
                $options['stripe_price_id']
                ?? $options['price_id']
                ?? ($options['price_map']['stripe_price_id'] ?? null)
            );

        $basemetadata = [
            'legacy_payment_request_id' =>
                $paymentrequestid,

            'legacy_payment_request_table' =>
                $paymentrequesttable,

            'legacy_payment_context' =>
                $paymentcontext,

            'legacy_payment_provider' =>
                $provider,

            'legacy_order_number_prefix' =>
                $ordernumberprefix,

            'legacy_language' =>
                $language,

            'legacy_mode' =>
                $mode,

            'legacy_stripe_price_id' =>
                $stripepriceid,

            'legacy_operation' =>
                $paymentrequest->operation
                ?? null,

            'legacy_plan_id' =>
                isset($paymentrequest->planid)
                    ? (int)$paymentrequest->planid
                    : null,

            'legacy_product_id' =>
                isset($paymentrequest->productid)
                    ? (int)$paymentrequest->productid
                    : null,

            'legacy_subscription_id' =>
                isset($paymentrequest->subscriptionid)
                    ? (int)$paymentrequest->subscriptionid
                    : null,

            'legacy_options_metadata' =>
                $options['metadata']
                ?? [],
        ];

        $metadata =
            array_merge(
                $basemetadata,
                $options['commerce_metadata']
                    ?? []
            );

        /*
         * Build the real immutable Legacy context.
         *
         * Besides validating the table/context/provider combination, this
         * guarantees that the metadata emitted by this factory is compatible
         * with LegacyPaymentRequestContext::from_metadata().
         */
        $legacycontext =
            new LegacyPaymentRequestContext(
                $paymentrequestid,
                $paymentrequesttable,
                $paymentcontext,
                $provider,
                $ordernumberprefix,
                $language,
                $mode,
                $stripepriceid,
                $metadata
            );

        $currency =
            strtoupper(
                trim(
                    (string)(
                        $paymentrequest->currency
                        ?? ''
                    )
                )
            );

        $amountminor =
            $this->resolve_amount_minor(
                $paymentrequest
            );

        $reference =
            sprintf(
                'legacy:%s:%d',
                $legacycontext->get_payment_context(),
                $legacycontext->get_payment_request_id()
            );

        return new CommercePaymentRequest(
            $reference,
            new CommercePaymentCustomer(
                !empty($paymentrequest->userid)
                    ? (int)$paymentrequest->userid
                    : null,
                (string)(
                    $paymentrequest->email
                    ?? ''
                ),
                $this->nullable_string(
                    $paymentrequest->firstname
                    ?? null
                ),
                $this->nullable_string(
                    $paymentrequest->lastname
                    ?? null
                ),
                [
                    'source' =>
                        'legacy_payment_request',

                    'paymentrequesttable' =>
                        $legacycontext
                            ->get_payment_request_table(),

                    'paymentrequestid' =>
                        $legacycontext
                            ->get_payment_request_id(),
                ]
            ),
            [
                new CommercePaymentLine(
                    $reference . ':line:1',
                    $this->resolve_description(
                        $paymentrequest,
                        $legacycontext,
                        $options
                    ),
                    1,
                    $amountminor,
                    $currency,
                    [
                        'legacy_payment_context' =>
                            $legacycontext
                                ->get_payment_context(),

                        'legacy_plan_id' =>
                            $metadata[
                                'legacy_plan_id'
                            ],

                        'legacy_product_id' =>
                            $metadata[
                                'legacy_product_id'
                            ],
                    ]
                ),
            ],
            $currency,
            $amountminor,
            $legacycontext->get_provider(),
            $this->resolve_url(
                $options,
                [
                    'success_url',
                    'returnurl',
                ]
            ),
            $this->resolve_url(
                $options,
                [
                    'cancel_url',
                    'failurl',
                ]
            ),
            $legacycontext->get_metadata(),
            time()
        );
    }

    private function resolve_payment_context(
        string $paymentrequesttable
    ): string {
        $table =
            strtolower(
                trim($paymentrequesttable)
            );

        return match ($table) {
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION =>
                LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,

            LegacyPaymentRequestContext::TABLE_DIGITAL =>
                LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,

            default =>
                throw new LegacyPaymentRequestMappingException(
                    'The requested Legacy payment table is not allowed.',
                    'legacy_payment_request_table_not_allowed',
                    [
                        'table' =>
                            $paymentrequesttable,
                    ]
                ),
        };
    }

    private function resolve_order_number_prefix(
        string $paymentrequesttable,
        array $options
    ): string {
        $configured =
            $this->nullable_string(
                $options['order_number_prefix']
                ?? null
            );

        if ($configured !== null) {
            return $configured;
        }

        return strtolower(
            trim($paymentrequesttable)
        ) === LegacyPaymentRequestContext::TABLE_DIGITAL
            ? 'digital'
            : 'sub';
    }

    private function resolve_amount_minor(
        \stdClass $paymentrequest
    ): int {
        if (
            isset($paymentrequest->amount_minor)
            && (int)$paymentrequest->amount_minor >= 0
        ) {
            return (int)$paymentrequest->amount_minor;
        }

        $major =
            (float)(
                $paymentrequest->locked_final_price
                ?? $paymentrequest->price
                ?? 0
            );

        return (int)round(
            $major * 100
        );
    }

    private function resolve_description(
        \stdClass $paymentrequest,
        LegacyPaymentRequestContext $context,
        array $options
    ): string {
        $description =
            trim(
                (string)(
                    $options['product_name']
                    ?? ''
                )
            );

        if ($description !== '') {
            return $description;
        }

        if (
            $context->is_subscription()
            && !empty($paymentrequest->planid)
        ) {
            return 'Subscription plan #'
                . (int)$paymentrequest->planid;
        }

        if (
            $context->is_digital()
            && !empty($paymentrequest->productid)
        ) {
            return 'Digital product #'
                . (int)$paymentrequest->productid;
        }

        return 'Commerce payment request #'
            . $context->get_payment_request_id();
    }

    private function resolve_url(
        array $options,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            $value =
                $this->nullable_string(
                    $options[$key]
                    ?? null
                );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if (
            $value === null
            || !is_scalar($value)
        ) {
            return null;
        }

        $value = trim(
            (string)$value
        );

        return $value !== ''
            ? $value
            : null;
    }
}
