<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\CommerceCheckoutEligibility;
use local_subscriptions\commerce\checkout\CommerceCheckoutResult;
use local_subscriptions\commerce\checkout\shadow\CommerceCheckoutComparison;
use local_subscriptions\commerce\checkout\shadow\CommerceCheckoutShadowReport;
use local_subscriptions\digital\product_manager;

/**
 * Tests for the progressive Commerce checkout migration.
 */
final class commerce_checkout_migration_test extends advanced_testcase {

    public function test_digital_stripe_eur_is_eligible(): void {
        $request = (object)[
            'payment_provider' => 'stripe',
            'currency' => 'eur',
        ];

        $this->assertTrue(
            (new CommerceCheckoutEligibility())->is_digital_stripe_eur(
                $request,
                product_manager::TABLE_PAYMENT_REQUEST
            )
        );
    }

    public function test_alfa_or_non_eur_is_not_eligible(): void {
        $eligibility = new CommerceCheckoutEligibility();

        $this->assertFalse(
            $eligibility->is_digital_stripe_eur(
                (object)['payment_provider' => 'alfa', 'currency' => 'EUR'],
                product_manager::TABLE_PAYMENT_REQUEST
            )
        );

        $this->assertFalse(
            $eligibility->is_digital_stripe_eur(
                (object)['payment_provider' => 'stripe', 'currency' => 'RUB'],
                product_manager::TABLE_PAYMENT_REQUEST
            )
        );
    }

    public function test_checkout_result_requires_supported_engine(): void {
        $this->expectException(\coding_exception::class);

        new CommerceCheckoutResult(
            'unknown',
            'https://payments.example.test/session'
        );
    }

    public function test_shadow_report_detects_difference(): void {
        $report = new CommerceCheckoutShadowReport(
            'legacy:digital_product:12',
            [
                new CommerceCheckoutComparison('currency', 'EUR', 'EUR'),
                new CommerceCheckoutComparison('amount_minor', 2900, 3000),
            ]
        );

        $this->assertFalse($report->is_compatible());
        $this->assertCount(2, $report->get_comparisons());
    }
}
