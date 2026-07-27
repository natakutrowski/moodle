<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingCalculator;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingConfiguration;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_bundle_pricing_test extends advanced_testcase {
    public function test_component_sum_quote_uses_component_total(): void {
        $quote = (new CommerceBundlePricingCalculator())->calculate(
            'BUNDLE.TEST',
            new CommerceBundlePricingConfiguration(CommerceBundlePricingStrategy::COMPONENT_SUM),
            CommerceMoney::from_minor(12500, 'EUR'),
            null
        );
        $this->assertSame(12500, $quote->get_final_price()->get_amount_minor());
        $this->assertSame(0, $quote->get_savings_minor());
    }

    public function test_percentage_discount_uses_integer_money(): void {
        $quote = (new CommerceBundlePricingCalculator())->calculate(
            'BUNDLE.TEST',
            new CommerceBundlePricingConfiguration(CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT, 1500),
            CommerceMoney::from_minor(10001, 'EUR'),
            null
        );
        $this->assertSame(8501, $quote->get_final_price()->get_amount_minor());
        $this->assertSame(1500, $quote->get_savings_minor());
    }

    public function test_fixed_strategy_requires_price(): void {
        $this->expectException(\coding_exception::class);
        (new CommerceBundlePricingCalculator())->calculate(
            'BUNDLE.TEST',
            new CommerceBundlePricingConfiguration(CommerceBundlePricingStrategy::FIXED),
            CommerceMoney::from_minor(10000, 'EUR'),
            null
        );
    }
}
