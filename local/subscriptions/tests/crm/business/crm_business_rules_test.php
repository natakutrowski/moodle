<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\business\PaymentStatus;
use local_subscriptions\crm\business\SubscriptionClassification;

/**
 * Tests for CRM business classification rules.
 *
 * @covers \local_subscriptions\crm\business\PaymentStatus
 * @covers \local_subscriptions\crm\business\SubscriptionClassification
 */
final class crm_business_rules_test extends advanced_testcase {

    public function test_payment_status_is_case_insensitive(): void {
        $this->assertTrue(
            PaymentStatus::is_successful('paid')
        );

        $this->assertTrue(
            PaymentStatus::is_successful('PAID')
        );

        $this->assertTrue(
            PaymentStatus::is_successful(' Completed ')
        );

        $this->assertFalse(
            PaymentStatus::is_successful('pending')
        );

        $this->assertFalse(
            PaymentStatus::is_successful(null)
        );
    }

    public function test_plan_flag_identifies_trial(): void {
        $subscription = (object)[
            'status' => 'active',
            'payment_provider' => 'stripe',
            'pricepaid' => 0,
            'plan_is_trial' => 1,
        ];

        $this->assertTrue(
            SubscriptionClassification::is_trial_record(
                $subscription
            )
        );

        $this->assertSame(
            SubscriptionClassification::TRIAL,
            SubscriptionClassification::classify(
                $subscription
            )
        );
    }

    public function test_status_does_not_define_trial_plan(): void {
        $subscription = (object)[
            'status' => 'trial',
            'payment_provider' => null,
            'pricepaid' => 0,
            'plan_is_trial' => 0,
        ];

        $this->assertFalse(
            SubscriptionClassification::is_trial_record(
                $subscription
            )
        );
    }

    public function test_plan_information_has_priority_over_provider(): void {
        $subscription = (object)[
            'status' => 'active',
            'payment_provider' => 'trial',
            'pricepaid' => 0,
            'plan_is_trial' => 0,
        ];

        $this->assertFalse(
            SubscriptionClassification::is_trial_record(
                $subscription
            )
        );
    }

    public function test_successful_payment_request_identifies_paid_subscription(): void {
        $subscription = (object)[
            'status' => 'active',
            'payment_provider' => 'stripe',
            'pricepaid' => 0,
            'plan_is_trial' => 0,
        ];

        $this->assertSame(
            SubscriptionClassification::PAID,
            SubscriptionClassification::classify(
                $subscription,
                true
            )
        );
    }

    public function test_legacy_price_identifies_paid_subscription(): void {
        $subscription = (object)[
            'status' => 'active',
            'payment_provider' => 'manual',
            'pricepaid' => 150,
            'plan_is_trial' => 0,
        ];

        $this->assertTrue(
            SubscriptionClassification::has_legacy_payment_evidence(
                $subscription
            )
        );

        $this->assertSame(
            SubscriptionClassification::PAID,
            SubscriptionClassification::classify(
                $subscription
            )
        );
    }

    public function test_trial_is_never_classified_as_paid(): void {
        $subscription = (object)[
            'status' => 'trial',
            'payment_provider' => 'trial',
            'pricepaid' => 50,
            'plan_is_trial' => 1,
        ];

        $this->assertSame(
            SubscriptionClassification::TRIAL,
            SubscriptionClassification::classify(
                $subscription,
                true
            )
        );
    }

    public function test_zero_price_non_trial_subscription_is_free(): void {
        $subscription = (object)[
            'status' => 'active',
            'payment_provider' => 'manual',
            'pricepaid' => 0,
            'plan_is_trial' => 0,
        ];

        $this->assertSame(
            SubscriptionClassification::FREE,
            SubscriptionClassification::classify(
                $subscription
            )
        );
    }
}