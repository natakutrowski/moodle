<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g6_admin_payment_presentation_policy_test extends advanced_testcase {
    public function test_missing_configuration_preserves_existing_methods_and_providers(): void {
        $this->resetAfterTest(true);

        unset_config(
            'commerce_presented_payment_methods',
            'local_subscriptions'
        );
        unset_config(
            'commerce_presented_payment_providers',
            'local_subscriptions'
        );

        $policy = new CommercePaymentPresentationPolicy();

        $this->assertTrue(
            $policy->is_provider_allowed('stripe')
        );
        $this->assertTrue(
            $policy->is_provider_allowed('alfa')
        );
        $this->assertTrue(
            $policy->is_provider_allowed('paypal')
        );
        $this->assertTrue(
            $policy->is_method_allowed('card')
        );
        $this->assertTrue(
            $policy->is_method_allowed('paypal')
        );
        $this->assertTrue(
            $policy->is_method_allowed('klarna')
        );
    }

    public function test_admin_can_restrict_provider_and_method_pool(): void {
        $this->resetAfterTest(true);

        set_config(
            'commerce_presented_payment_providers',
            'stripe,paypal',
            'local_subscriptions'
        );
        set_config(
            'commerce_presented_payment_methods',
            'card,paypal',
            'local_subscriptions'
        );

        $policy = new CommercePaymentPresentationPolicy();

        $this->assertTrue(
            $policy->is_provider_allowed('stripe')
        );
        $this->assertFalse(
            $policy->is_provider_allowed('alfa')
        );
        $this->assertTrue(
            $policy->is_method_allowed('card')
        );
        $this->assertFalse(
            $policy->is_method_allowed('klarna')
        );
    }

    public function test_admin_can_intentionally_disable_all_customer_methods(): void {
        $this->resetAfterTest(true);

        set_config(
            'commerce_presented_payment_methods',
            '',
            'local_subscriptions'
        );

        $policy = new CommercePaymentPresentationPolicy();

        $this->assertSame(
            [],
            $policy->allowed_methods()
        );
    }
}
