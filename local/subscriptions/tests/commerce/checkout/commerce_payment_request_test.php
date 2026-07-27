<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;

/**
 * Tests for provider-independent Commerce payment requests.
 *
 * @covers \local_subscriptions\commerce\payment\CommercePaymentCustomer
 * @covers \local_subscriptions\commerce\payment\CommercePaymentLine
 * @covers \local_subscriptions\commerce\payment\CommercePaymentRequest
 */
final class commerce_payment_request_test
    extends advanced_testcase {

    public function test_payment_request_calculates_consistent_total():
        void {
        $request =
            new CommercePaymentRequest(
                'payment:test',
                new CommercePaymentCustomer(
                    96,
                    'student@example.com'
                ),
                [
                    new CommercePaymentLine(
                        'subscription-plan:14',
                        'CampusFR A1',
                        1,
                        12000,
                        'EUR'
                    ),

                    new CommercePaymentLine(
                        'digital-product:8',
                        'PDF des verbes',
                        1,
                        1900,
                        'EUR'
                    ),
                ],
                'EUR',
                13900,
                'stripe'
            );

        $this->assertSame(
            13900,
            $request->get_amount_minor()
        );

        $this->assertSame(
            'EUR',
            $request->get_currency()
        );

        $this->assertSame(
            'stripe',
            $request->get_preferred_provider()
        );

        $this->assertTrue(
            $request->contains_multiple_lines()
        );

        $this->assertTrue(
            $request->requires_payment()
        );
    }

    public function test_amount_mismatch_is_rejected():
        void {
        $this->expectException(
            \coding_exception::class
        );

        new CommercePaymentRequest(
            'payment:mismatch',
            new CommercePaymentCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePaymentLine(
                    'digital-product:8',
                    'PDF des verbes',
                    1,
                    1900,
                    'EUR'
                ),
            ],
            'EUR',
            2000
        );
    }

    public function test_mixed_currency_lines_are_rejected():
        void {
        $this->expectException(
            \coding_exception::class
        );

        new CommercePaymentRequest(
            'payment:mixed',
            new CommercePaymentCustomer(
                96,
                'student@example.com'
            ),
            [
                new CommercePaymentLine(
                    'subscription-plan:14',
                    'CampusFR A1',
                    1,
                    12000,
                    'EUR'
                ),

                new CommercePaymentLine(
                    'digital-product:8',
                    'PDF des verbes',
                    1,
                    150000,
                    'RUB'
                ),
            ],
            'EUR',
            162000
        );
    }

    public function test_zero_amount_payment_is_supported():
        void {
        $request =
            new CommercePaymentRequest(
                'payment:free',
                new CommercePaymentCustomer(
                    96,
                    'student@example.com'
                ),
                [
                    new CommercePaymentLine(
                        'subscription-plan:trial',
                        'CampusFR Trial',
                        1,
                        0,
                        'EUR'
                    ),
                ],
                'EUR',
                0
            );

        $this->assertTrue(
            $request->is_free()
        );

        $this->assertFalse(
            $request->requires_payment()
        );
    }
}