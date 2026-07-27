<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;

/**
 * Tests deterministic Commerce provider contexts.
 *
 * @covers \local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory
 */
final class commerce_payment_provider_context_factory_test
    extends advanced_testcase {

    public function test_same_request_produces_same_idempotency_key():
        void {
        $factory =
            new CommercePaymentProviderContextFactory();

        $request =
            $this->create_request();

        $first =
            $factory->create(
                $request,
                false
            );

        $second =
            $factory->create(
                $request,
                false
            );

        $this->assertSame(
            $first->get_idempotency_key(),
            $second->get_idempotency_key()
        );

        $this->assertLessThanOrEqual(
            255,
            strlen(
                $first->get_idempotency_key()
            )
        );

        $this->assertTrue(
            $first->is_test()
        );
    }

    public function test_amount_change_produces_another_idempotency_key():
        void {
        $factory =
            new CommercePaymentProviderContextFactory();

        $first =
            $factory->create(
                $this->create_request(
                    12000
                ),
                false
            );

        $second =
            $factory->create(
                $this->create_request(
                    12500
                ),
                false
            );

        $this->assertNotSame(
            $first->get_idempotency_key(),
            $second->get_idempotency_key()
        );
    }

    public function test_provider_change_produces_another_idempotency_key():
        void {
        $factory =
            new CommercePaymentProviderContextFactory();

        $stripe =
            $factory->create(
                $this->create_request(
                    12000,
                    'stripe'
                ),
                false
            );

        $alfa =
            $factory->create(
                $this->create_request(
                    12000,
                    'alfa'
                ),
                false
            );

        $this->assertNotSame(
            $stripe->get_idempotency_key(),
            $alfa->get_idempotency_key()
        );
    }

    public function test_legacy_request_identity_is_included():
        void {
        $factory =
            new CommercePaymentProviderContextFactory();

        $context =
            $factory->create(
                $this->create_request(),
                true,
                [
                    'source' =>
                        'phpunit',
                ]
            );

        $key =
            $context->get_idempotency_key();

        $this->assertStringStartsWith(
            'commerce:subscription_payment_request:42:',
            $key
        );

        $this->assertTrue(
            $context->is_live()
        );

        $this->assertSame(
            'phpunit',
            $context->get_metadata_value(
                'source'
            )
        );

        $this->assertSame(
            'payment:test:42',
            $context->get_metadata_value(
                'requestreference'
            )
        );
    }

    public function test_request_without_legacy_identity_uses_hash_key():
        void {
        $factory =
            new CommercePaymentProviderContextFactory();

        $request =
            new CommercePaymentRequest(
                'payment:standalone:1',
                new CommercePaymentCustomer(
                    96,
                    'student@example.com',
                    'Test',
                    'Student'
                ),
                [
                    new CommercePaymentLine(
                        'payment:standalone:1:line',
                        'Standalone payment',
                        1,
                        500,
                        'EUR'
                    ),
                ],
                'EUR',
                500,
                'stripe'
            );

        $key =
            $factory
                ->create(
                    $request,
                    false
                )
                ->get_idempotency_key();

        $this->assertStringStartsWith(
            'commerce:',
            $key
        );

        $this->assertStringNotContainsString(
            'subscription_payment_request',
            $key
        );
    }

    private function create_request(
        int $amountminor = 12000,
        string $provider = 'stripe'
    ): CommercePaymentRequest {
        return new CommercePaymentRequest(
            'payment:test:42',
            new CommercePaymentCustomer(
                96,
                'student@example.com',
                'Test',
                'Student'
            ),
            [
                new CommercePaymentLine(
                    'payment:test:42:line',
                    'Test subscription',
                    1,
                    $amountminor,
                    'EUR'
                ),
            ],
            'EUR',
            $amountminor,
            $provider,
            'https://example.test/success',
            'https://example.test/cancel',
            [
                'legacy_payment_request_id' =>
                    42,

                'legacy_payment_request_table' =>
                    'subscription_payment_request',
            ]
        );
    }
}
