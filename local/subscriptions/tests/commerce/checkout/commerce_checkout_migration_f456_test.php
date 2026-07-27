<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\CommerceCheckoutEligibility;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\digital\product_manager;

/**
 * Eligibility tests for phases 7.93F.4 to F.6.
 */
final class commerce_checkout_migration_f456_test extends advanced_testcase {

    public function test_subscription_stripe_eur_is_eligible(): void {
        $this->assertTrue((new CommerceCheckoutEligibility())->is_subscription_stripe_eur(
            (object)['payment_provider' => 'stripe', 'currency' => 'eur'],
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
        ));
    }

    public function test_alfa_rub_is_eligible_for_both_purchase_types(): void {
        $eligibility = new CommerceCheckoutEligibility();
        $request = (object)['payment_provider' => 'alfa', 'currency' => 'rub'];

        $this->assertTrue($eligibility->is_subscription_alfa_rub(
            $request,
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
        ));
        $this->assertTrue($eligibility->is_digital_alfa_rub(
            $request,
            product_manager::TABLE_PAYMENT_REQUEST
        ));
    }

    public function test_wrong_currency_or_table_is_rejected(): void {
        $eligibility = new CommerceCheckoutEligibility();

        $this->assertFalse($eligibility->is_subscription_stripe_eur(
            (object)['payment_provider' => 'stripe', 'currency' => 'RUB'],
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
        ));
        $this->assertFalse($eligibility->is_digital_alfa_rub(
            (object)['payment_provider' => 'alfa', 'currency' => 'RUB'],
            LegacyPaymentRequestContext::TABLE_SUBSCRIPTION
        ));
    }
}
