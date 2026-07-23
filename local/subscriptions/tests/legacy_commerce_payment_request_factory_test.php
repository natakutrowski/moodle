<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestMappingException;
use local_subscriptions\payment\Provider;

/**
 * Tests Legacy-to-Commerce payment request mapping.
 *
 * @covers \local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory
 */
final class legacy_commerce_payment_request_factory_test
    extends advanced_testcase {

    public function test_subscription_request_is_mapped_to_stripe_commerce_request():
        void {
        $factory =
            new LegacyCommercePaymentRequestFactory();

        $request =
            $factory->create(
                $this->create_subscription_request(),
                LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                [
                    'returnurl' =>
                        'https://example.test/subscription/success',

                    'failurl' =>
                        'https://example.test/subscription/cancel',

                    'language' =>
                        'fr',

                    'mode' =>
                        'subscription',

                    'stripe_price_id' =>
                        'price_test_123',

                    'product_name' =>
                        'CampusFR annual plan',
                ]
            );

        $this->assertSame(
            'legacy:subscription:41',
            $request->get_reference()
        );

        $this->assertSame(
            Provider::STRIPE,
            $request->get_preferred_provider()
        );

        $this->assertSame(
            'EUR',
            $request->get_currency()
        );

        $this->assertSame(
            12000,
            $request->get_amount_minor()
        );

        $this->assertSame(
            'https://example.test/subscription/success',
            $request->get_return_url()
        );

        $this->assertSame(
            'https://example.test/subscription/cancel',
            $request->get_cancel_url()
        );

        $this->assertSame(
            96,
            $request
                ->get_customer()
                ->get_user_id()
        );

        $this->assertSame(
            'student@example.com',
            $request
                ->get_customer()
                ->get_email()
        );

        $this->assertSame(
            'CampusFR annual plan',
            $request
                ->get_lines()[0]
                ->get_description()
        );

        $metadata =
            $request->get_metadata();

        $this->assertSame(
            41,
            $metadata[
                'legacy_payment_request_id'
            ]
        );

        $this->assertSame(
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
            $metadata[
                'legacy_payment_request_table'
            ]
        );

        $this->assertSame(
            LegacyPaymentRequestContext::CONTEXT_SUBSCRIPTION,
            $metadata[
                'legacy_payment_context'
            ]
        );

        $this->assertSame(
            'sub',
            $metadata[
                'legacy_order_number_prefix'
            ]
        );

        $this->assertSame(
            'fr',
            $metadata[
                'legacy_language'
            ]
        );

        $this->assertSame(
            'subscription',
            $metadata[
                'legacy_mode'
            ]
        );

        $this->assertSame(
            'price_test_123',
            $metadata[
                'legacy_stripe_price_id'
            ]
        );

        /*
         * The emitted metadata must be consumable by the real context parser.
         */
        $context =
            LegacyPaymentRequestContext::from_metadata(
                $metadata,
                Provider::STRIPE
            );

        $this->assertTrue(
            $context->is_subscription()
        );

        $this->assertSame(
            'sub-41',
            $context->get_order_number()
        );
    }

    public function test_digital_request_is_mapped_to_alfa_commerce_request():
        void {
        $factory =
            new LegacyCommercePaymentRequestFactory();

        $request =
            $factory->create(
                $this->create_digital_request(),
                LegacyPaymentRequestContext::TABLE_DIGITAL,
                [
                    'success_url' =>
                        'https://example.test/digital/success',

                    'cancel_url' =>
                        'https://example.test/digital/cancel',

                    'language' =>
                        'ru',

                    'commerce_metadata' => [
                        'channel' =>
                            'digital-product-page',
                    ],
                ]
            );

        $this->assertSame(
            'legacy:digital_product:84',
            $request->get_reference()
        );

        $this->assertSame(
            Provider::ALFA,
            $request->get_preferred_provider()
        );

        $this->assertSame(
            'RUB',
            $request->get_currency()
        );

        $this->assertSame(
            250000,
            $request->get_amount_minor()
        );

        $this->assertSame(
            'Digital product #17',
            $request
                ->get_lines()[0]
                ->get_description()
        );

        $metadata =
            $request->get_metadata();

        $this->assertSame(
            LegacyPaymentRequestContext::CONTEXT_DIGITAL_PRODUCT,
            $metadata[
                'legacy_payment_context'
            ]
        );

        $this->assertSame(
            'digital',
            $metadata[
                'legacy_order_number_prefix'
            ]
        );

        $this->assertSame(
            17,
            $metadata[
                'legacy_product_id'
            ]
        );

        $this->assertSame(
            'digital-product-page',
            $metadata['channel']
        );

        $context =
            LegacyPaymentRequestContext::from_metadata(
                $metadata,
                Provider::ALFA
            );

        $this->assertTrue(
            $context->is_digital()
        );

        $this->assertSame(
            'digital-84',
            $context->get_order_number()
        );
    }

    public function test_amount_minor_is_used_without_recalculation():
        void {
        $record =
            $this->create_subscription_request();

        $record->amount_minor = 12345;
        $record->locked_final_price = 999.99;

        $request =
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $record,
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
                );

        $this->assertSame(
            12345,
            $request->get_amount_minor()
        );
    }

    public function test_locked_final_price_is_used_as_fallback():
        void {
        $record =
            $this->create_subscription_request();

        unset(
            $record->amount_minor
        );

        $record->locked_final_price =
            123.45;

        $request =
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $record,
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
                );

        $this->assertSame(
            12345,
            $request->get_amount_minor()
        );
    }

    public function test_price_is_used_when_locked_price_is_missing():
        void {
        $record =
            $this->create_subscription_request();

        unset(
            $record->amount_minor,
            $record->locked_final_price
        );

        $record->price =
            87.65;

        $request =
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $record,
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
                );

        $this->assertSame(
            8765,
            $request->get_amount_minor()
        );
    }

    public function test_custom_order_number_prefix_is_preserved():
        void {
        $request =
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $this->create_subscription_request(),
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
                    [
                        'order_number_prefix' =>
                            'renewal',
                    ]
                );

        $this->assertSame(
            'renewal',
            $request->get_metadata_value(
                'legacy_order_number_prefix'
            )
        );
    }

    public function test_unknown_table_is_rejected():
        void {
        $this->expectException(
            LegacyPaymentRequestMappingException::class
        );

        try {
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $this->create_subscription_request(),
                    'arbitrary_payment_table'
                );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_request_table_not_allowed',
                $exception->get_mapping_code()
            );

            throw $exception;
        }
    }

    public function test_request_without_identifier_is_rejected():
        void {
        $record =
            $this->create_subscription_request();

        unset(
            $record->id
        );

        $this->expectException(
            LegacyPaymentRequestMappingException::class
        );

        try {
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $record,
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
                );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_request_id_missing',
                $exception->get_mapping_code()
            );

            throw $exception;
        }
    }

    public function test_request_without_provider_is_rejected():
        void {
        $record =
            $this->create_subscription_request();

        $record->payment_provider = '';

        $this->expectException(
            LegacyPaymentRequestMappingException::class
        );

        try {
            (new LegacyCommercePaymentRequestFactory())
                ->create(
                    $record,
                    LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
                );
        } catch (
            LegacyPaymentRequestMappingException $exception
        ) {
            $this->assertSame(
                'legacy_payment_provider_missing',
                $exception->get_mapping_code()
            );

            throw $exception;
        }
    }

    private function create_subscription_request():
        \stdClass {
        return (object)[
            'id' =>
                41,

            'planid' =>
                14,

            'subscriptionid' =>
                27,

            'userid' =>
                96,

            'email' =>
                'student@example.com',

            'firstname' =>
                'Test',

            'lastname' =>
                'Student',

            'currency' =>
                'EUR',

            'price' =>
                120.00,

            'amount_minor' =>
                12000,

            'payment_provider' =>
                Provider::STRIPE,

            'operation' =>
                'purchase_new',
        ];
    }

    private function create_digital_request():
        \stdClass {
        return (object)[
            'id' =>
                84,

            'productid' =>
                17,

            'userid' =>
                96,

            'email' =>
                'student@example.com',

            'firstname' =>
                'Test',

            'lastname' =>
                'Student',

            'currency' =>
                'RUB',

            'price' =>
                2500.00,

            'amount_minor' =>
                250000,

            'payment_provider' =>
                Provider::ALFA,

            'operation' =>
                'purchase_new',
        ];
    }
}
