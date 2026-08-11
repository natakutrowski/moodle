<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Persisted pricing harmonisation for Order Details and CRM. */
final class commerce_persisted_pricing_surfaces_test
        extends \advanced_testcase {

    public function test_presenter_exports_percentages_and_upgrade_path(): void {
        $presenter = new \local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter();

        $model = $presenter->item([
            'pricing_initial_total_minor' => 20000,
            'pricing_promotion_total_minor' => 3000,
            'pricing_trial_discount_total_minor' => 3400,
            'pricing_owned_credit_total_minor' => 8000,
            'pricing_final_total_minor' => 5600,
            'pricing_operation' => 'upgrade',
            'pricing_upgrade_from_label' => 'A2 Grammar',
            'pricing_upgrade_to_label' => 'A2 Full',
        ], 20000, 14400, 5600, 1);

        $this->assertSame(15, $model['promotionpercent']);
        $this->assertSame(20, $model['trialpercent']);
        $this->assertTrue($model['isupgrade']);
        $this->assertTrue($model['hasupgradepath']);
        $this->assertSame('A2 Full', $model['tolabel']);
    }

    public function test_order_details_uses_compact_persisted_pricing(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache'
        );

        $this->assertStringContainsString('{{finalprice}}', $template);
        $this->assertStringContainsString('{{compareprice}}', $template);
        $this->assertStringContainsString(
            'commerce-order-item-detail__pricing-details',
            $template
        );
        $this->assertStringContainsString(
            'commerce_cart_trial_discount_total',
            $template
        );
        $this->assertStringContainsString(
            'commerce_cart_upgrade_credit_total',
            $template
        );
    }

    public function test_crm_uses_same_pricing_taxonomy(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            'commerce_cart_product_promotions_total',
            $source
        );
        $this->assertStringContainsString(
            'commerce_cart_trial_discount_total',
            $source
        );
        $this->assertStringContainsString(
            'commerce_cart_upgrade_credit_total',
            $source
        );
        $this->assertStringContainsString(
            'commerce_pricing_initial_promotion_percent',
            $source
        );
    }
}
