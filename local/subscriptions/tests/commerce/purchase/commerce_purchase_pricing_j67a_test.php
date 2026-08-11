<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7A persisted commercial pricing and CRM presentation contract. */
final class commerce_purchase_pricing_j67a_test
        extends \advanced_testcase {

    public function test_builder_persists_complete_pricing_metadata(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutPurchaseBuilder.php'
        );

        $this->assertIsString($source);
        foreach ([
            'locked_list_total_minor',
            'locked_product_promotion_minor',
            'locked_trial_discount_minor',
            'cart_list_total_minor',
            'cart_product_promotion_minor',
            'cart_trial_discount_minor',
            'cart_discount_minor',
        ] as $key) {
            $this->assertStringContainsString($key, $source);
        }
        $this->assertStringContainsString(
            '$isupgrade || $istrialconversion',
            $source
        );
    }

    public function test_checkout_order_and_crm_show_promotions(): void {
        global $CFG;
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $order = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache');
        $crm = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php');
        $this->assertStringContainsString('hasproductpromotiontotal', $checkout);
        $this->assertStringContainsString('commerce_cart_product_promotions_total', $order);
        $this->assertStringContainsString('commerce_cart_product_promotions_total', $crm);
    }
}
