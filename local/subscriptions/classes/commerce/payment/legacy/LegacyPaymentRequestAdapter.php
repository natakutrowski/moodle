<?php

namespace local_subscriptions\commerce\payment\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\payment\Provider;

/**
 * Loads and validates a persisted Legacy payment request for Commerce.
 *
 * This adapter performs no database write and no provider call.
 */
final class LegacyPaymentRequestAdapter {

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public function load_for_commerce_request(
        CommercePaymentRequest $request,
        string $expectedprovider
    ): \stdClass {
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $request->get_metadata(),
                $expectedprovider
            );

        return $this->load_and_validate(
            $context,
            $request->get_amount_minor(),
            $request->get_currency(),
            $request->get_customer()->get_email(),
            $request->get_customer()->get_user_id()
        );
    }

    public function load_and_validate(
        LegacyPaymentRequestContext $context,
        int $expectedamountminor,
        string $expectedcurrency,
        string $expectedemail,
        ?int $expecteduserid = null
    ): \stdClass {
        if ($expectedamountminor <= 0) {
            throw new LegacyPaymentRequestMappingException(
                'A Legacy provider bridge requires a positive amount.',
                'legacy_expected_amount_invalid',
                [
                    'amountminor' =>
                        $expectedamountminor,
                ]
            );
        }

        $record = $this->db->get_record(
            $context->get_payment_request_table(),
            [
                'id' =>
                    $context->get_payment_request_id(),
            ],
            '*',
            IGNORE_MISSING
        );

        if (!$record) {
            throw new LegacyPaymentRequestMappingException(
                'The persisted Legacy payment request was not found.',
                'legacy_payment_request_not_found',
                [
                    'id' =>
                        $context->get_payment_request_id(),

                    'table' =>
                        $context->get_payment_request_table(),
                ]
            );
        }

        $this->validate_provider(
            $record,
            $context
        );

        $this->validate_amount(
            $record,
            $expectedamountminor,
            $context
        );

        $this->validate_currency(
            $record,
            $expectedcurrency,
            $context
        );

        $this->validate_email(
            $record,
            $expectedemail,
            $context
        );

        $this->validate_user(
            $record,
            $expecteduserid,
            $context
        );

        $this->validate_entity_reference(
            $record,
            $context
        );

        return $record;
    }

    private function validate_provider(
        \stdClass $record,
        LegacyPaymentRequestContext $context
    ): void {
        $legacyprovider = strtolower(
            trim(
                (string)(
                    $record->payment_provider
                    ?? ''
                )
            )
        );

        if ($legacyprovider === '') {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request has no provider.',
                'legacy_payment_provider_missing',
                $this->base_context($context)
            );
        }

        if (
            !in_array(
                $legacyprovider,
                [
                    Provider::STRIPE,
                    Provider::ALFA,
                ],
                true
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request uses an unsupported provider.',
                'legacy_payment_provider_unsupported',
                array_merge(
                    $this->base_context($context),
                    [
                        'legacyprovider' =>
                            $legacyprovider,
                    ]
                )
            );
        }

        if (
            $legacyprovider
            !== $context->get_provider()
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Commerce provider does not match the persisted Legacy provider.',
                'legacy_payment_provider_mismatch',
                array_merge(
                    $this->base_context($context),
                    [
                        'expectedprovider' =>
                            $context->get_provider(),

                        'legacyprovider' =>
                            $legacyprovider,
                    ]
                )
            );
        }
    }

    private function validate_amount(
        \stdClass $record,
        int $expectedamountminor,
        LegacyPaymentRequestContext $context
    ): void {
        $legacyamountminor =
            $this->resolve_amount_minor(
                $record
            );

        if ($legacyamountminor <= 0) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request has no valid locked amount.',
                'legacy_payment_amount_missing',
                array_merge(
                    $this->base_context($context),
                    [
                        'amountminor' =>
                            $legacyamountminor,
                    ]
                )
            );
        }

        if (
            $legacyamountminor
            !== $expectedamountminor
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Commerce amount does not match the persisted Legacy amount.',
                'legacy_payment_amount_mismatch',
                array_merge(
                    $this->base_context($context),
                    [
                        'expectedamountminor' =>
                            $expectedamountminor,

                        'legacyamountminor' =>
                            $legacyamountminor,
                    ]
                )
            );
        }
    }

    private function validate_currency(
        \stdClass $record,
        string $expectedcurrency,
        LegacyPaymentRequestContext $context
    ): void {
        $legacycurrency = strtoupper(
            trim(
                (string)(
                    $record->currency
                    ?? ''
                )
            )
        );

        $expectedcurrency = strtoupper(
            trim($expectedcurrency)
        );

        if (
            $legacycurrency === ''
            || !preg_match(
                '/^[A-Z]{3}$/',
                $legacycurrency
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request has an invalid currency.',
                'legacy_payment_currency_invalid',
                array_merge(
                    $this->base_context($context),
                    [
                        'legacycurrency' =>
                            $legacycurrency,
                    ]
                )
            );
        }

        if (
            $legacycurrency
            !== $expectedcurrency
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Commerce currency does not match the persisted Legacy currency.',
                'legacy_payment_currency_mismatch',
                array_merge(
                    $this->base_context($context),
                    [
                        'expectedcurrency' =>
                            $expectedcurrency,

                        'legacycurrency' =>
                            $legacycurrency,
                    ]
                )
            );
        }
    }

    private function validate_email(
        \stdClass $record,
        string $expectedemail,
        LegacyPaymentRequestContext $context
    ): void {
        $legacyemail = \core_text::strtolower(
            trim(
                (string)(
                    $record->email
                    ?? ''
                )
            )
        );

        $expectedemail = \core_text::strtolower(
            trim($expectedemail)
        );

        if (
            !validate_email($legacyemail)
            || !validate_email($expectedemail)
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy or Commerce customer email is invalid.',
                'legacy_payment_email_invalid',
                $this->base_context($context)
            );
        }

        if ($legacyemail !== $expectedemail) {
            throw new LegacyPaymentRequestMappingException(
                'The Commerce customer email does not match the persisted Legacy customer.',
                'legacy_payment_email_mismatch',
                array_merge(
                    $this->base_context($context),
                    [
                        'expectedemail' =>
                            $expectedemail,

                        'legacyemail' =>
                            $legacyemail,
                    ]
                )
            );
        }
    }

    private function validate_user(
        \stdClass $record,
        ?int $expecteduserid,
        LegacyPaymentRequestContext $context
    ): void {
        if ($expecteduserid === null) {
            return;
        }

        $legacyuserid = isset($record->userid)
            ? (int)$record->userid
            : 0;

        if (
            $legacyuserid > 0
            && $legacyuserid !== $expecteduserid
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Commerce user does not match the persisted Legacy user.',
                'legacy_payment_user_mismatch',
                array_merge(
                    $this->base_context($context),
                    [
                        'expecteduserid' =>
                            $expecteduserid,

                        'legacyuserid' =>
                            $legacyuserid,
                    ]
                )
            );
        }
    }

    private function validate_entity_reference(
        \stdClass $record,
        LegacyPaymentRequestContext $context
    ): void {
        if (
            $context->is_subscription()
            && (
                !isset($record->planid)
                || (int)$record->planid <= 0
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy subscription payment request has no valid plan.',
                'legacy_subscription_plan_missing',
                $this->base_context($context)
            );
        }

        if (
            $context->is_digital()
            && (
                !isset($record->productid)
                || (int)$record->productid <= 0
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy digital payment request has no valid product.',
                'legacy_digital_product_missing',
                $this->base_context($context)
            );
        }
    }

    private function resolve_amount_minor(
        \stdClass $record
    ): int {
        if (
            isset($record->amount_minor)
            && (int)$record->amount_minor > 0
        ) {
            return (int)$record->amount_minor;
        }

        if (
            isset($record->locked_final_price)
            && (float)$record->locked_final_price > 0
        ) {
            return (int)round(
                (float)$record->locked_final_price
                * 100
            );
        }

        if (
            isset($record->price)
            && (float)$record->price > 0
        ) {
            return (int)round(
                (float)$record->price
                * 100
            );
        }

        return 0;
    }

    private function base_context(
        LegacyPaymentRequestContext $context
    ): array {
        return [
            'id' =>
                $context->get_payment_request_id(),

            'table' =>
                $context->get_payment_request_table(),

            'paymentcontext' =>
                $context->get_payment_context(),

            'provider' =>
                $context->get_provider(),
        ];
    }
}