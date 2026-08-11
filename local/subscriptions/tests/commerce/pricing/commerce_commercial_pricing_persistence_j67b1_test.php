<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B1 canonical commercial-pricing persistence contract. */
final class commerce_commercial_pricing_persistence_j67b1_test
        extends \advanced_testcase {

    public function test_builder_uses_canonical_breakdown_and_persists_all_stages(): void {
        global $CFG;

        $builder = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutPurchaseBuilder.php'
        );
        $breakdown = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/pricing/'
            . 'CommerceCommercialPriceBreakdown.php'
        );

        $this->assertIsString($builder);
        $this->assertIsString($breakdown);

        $this->assertStringContainsString(
            'CommerceCommercialPriceResolver',
            $builder
        );
        $this->assertStringContainsString(
            '$pricing->to_metadata()',
            $builder
        );

        foreach ([
            'cart_owned_credit_minor',
            'cart_upgrade_total_minor',
            'cart_product_promotion_minor',
            'cart_trial_discount_minor',
            "'pricing_schema' => 'commercial_breakdown_v1'",
        ] as $key) {
            $this->assertStringContainsString($key, $builder);
        }

        foreach ([
            'pricing_owned_credit_total_minor',
            'pricing_upgrade_total_minor',
            'pricing_promotion_total_minor',
            'pricing_trial_discount_total_minor',
            'pricing_final_total_minor',
        ] as $key) {
            $this->assertStringContainsString($key, $breakdown);
        }
    }
}
