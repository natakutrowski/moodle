<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B7 invoice, Order Details and CRM integration contract. */
final class commerce_pricing_documents_j67b7_test
        extends \advanced_testcase {

    public function test_all_persistent_surfaces_use_shared_presenter(): void {
        global $CFG;

        foreach ([
            'classes/commerce/order/invoice/CommerceInvoicePdfService.php',
            'order_details.php',
            'admin/commerce/purchases/view.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            $this->assertIsString($source);
            $this->assertStringContainsString(
                'CommercePersistedCommercialPricingPresenter',
                $source
            );
        }
    }

    public function test_invoice_shows_initial_price_credit_and_paid_total(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/order/invoice/'
            . 'CommerceInvoicePdfService.php'
        );

        $this->assertStringContainsString(
            'commerce_invoice_item_paid_price',
            $source
        );
        $this->assertStringContainsString(
            'commerce_invoice_owned_credit',
            $source
        );
        $this->assertStringContainsString(
            'commerce_cart_total_reductions',
            $source
        );
    }

    public function test_order_and_crm_include_owned_credit(): void {
        global $CFG;
        $order = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/order_details/page.mustache');
        $crm = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php');
        $this->assertStringContainsString('commerce_cart_upgrade_credit_total', $order);
        $this->assertStringContainsString('commerce-order-item-detail__pricing-details', $order);
        $this->assertStringContainsString('commerce_cart_upgrade_credit_total', $crm);
        $this->assertStringContainsString("['creditminor']", $crm);
    }
}
