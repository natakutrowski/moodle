<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;

/**
 * @covers \local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt
 * @covers \local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus
 */
final class commerce_payment_attempt_test extends advanced_testcase {

    public function test_attempt_normalises_provider_currency_and_status(): void {
        $attempt = new CommercePaymentAttempt(
            17,
            '0123456789abcdef0123456789abcdef',
            2,
            ' Stripe ',
            'created',
            8900,
            'eur',
            metadata: ['source' => 'checkout']
        );

        $this->assertSame(17, $attempt->get_id());
        $this->assertSame('stripe', $attempt->get_provider());
        $this->assertSame('EUR', $attempt->get_currency());
        $this->assertSame(CommercePaymentAttemptStatus::CREATED, $attempt->get_status());
        $this->assertSame(['source' => 'checkout'], $attempt->get_metadata());
    }

    public function test_invalid_purchase_uuid_is_rejected(): void {
        $this->expectException(\InvalidArgumentException::class);

        new CommercePaymentAttempt(
            null,
            'not-a-commerce-uuid',
            0,
            'stripe',
            CommercePaymentAttemptStatus::CREATED,
            8900,
            'EUR'
        );
    }

    public function test_terminal_statuses_are_explicit(): void {
        $this->assertFalse(
            CommercePaymentAttemptStatus::is_terminal(
                CommercePaymentAttemptStatus::REDIRECTED
            )
        );
        $this->assertTrue(
            CommercePaymentAttemptStatus::is_terminal(
                CommercePaymentAttemptStatus::PAID
            )
        );
    }
}
