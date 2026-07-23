<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;

/**
 * Tests for Commerce payment provider value objects.
 *
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationIssue
 * @covers \local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult
 */
final class commerce_payment_provider_contract_test
    extends advanced_testcase {

    public function test_provider_context_exposes_idempotency():
        void {
        $context =
            new CommercePaymentProviderContext(
                'commerce:purchase:test',
                false,
                [
                    'source' => 'phpunit',
                ]
            );

        $this->assertSame(
            'commerce:purchase:test',
            $context->get_idempotency_key()
        );

        $this->assertTrue(
            $context->is_test()
        );
    }

    public function test_capabilities_support_currency():
        void {
        $capabilities =
            new CommercePaymentProviderCapabilities(
                [
                    'EUR',
                    'RUB',
                ],
                true,
                true,
                true
            );

        $this->assertTrue(
            $capabilities->supports_currency('eur')
        );

        $this->assertTrue(
            $capabilities->supports_currency('RUB')
        );

        $this->assertFalse(
            $capabilities->supports_currency('USD')
        );
    }

    public function test_validation_warnings_do_not_invalidate_provider():
        void {
        $result =
            CommercePaymentProviderValidationResult::valid();

        $result->add_warning(
            'customer_phone_missing',
            'The customer phone number is missing.'
        );

        $this->assertTrue(
            $result->is_valid()
        );

        $this->assertTrue(
            $result->has_warnings()
        );
    }

    public function test_validation_errors_invalidate_provider():
        void {
        $result =
            CommercePaymentProviderValidationResult::valid();

        $result->add_error(
            'currency_not_supported',
            'The currency is not supported.'
        );

        $this->assertFalse(
            $result->is_valid()
        );
    }
}