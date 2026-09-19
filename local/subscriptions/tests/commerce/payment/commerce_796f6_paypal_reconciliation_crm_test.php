<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f6_paypal_reconciliation_crm_test extends advanced_testcase {
    public function test_purchase_surfaces_expose_paypal_reconciliation(): void {
        global $CFG;

        $view = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        $index = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/index.php'
        );

        foreach ([$view, $index] as $contents) {
            $this->assertStringContainsString(
                'reconcile_paypal.php',
                $contents
            );
        }
    }

    public function test_paypal_reconciliation_page_compares_payment_and_refunds(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/reconcile_paypal.php'
        );

        foreach ([
            'PayPalPaymentReconciliationService::create',
            'commerce_paypal_crm_refunded_campus',
            'commerce_paypal_crm_refunded_paypal',
            'commerce_paypal_crm_net_amount',
            'commerce_paypal_crm_check_refunds',
            '$inspection->reconcilable',
            'reconcile_payment(',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_paypal_statuses_are_presented_through_translation_layer(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/'
            . 'CommercePaymentReconciliationPresentation.php'
        );

        $this->assertStringContainsString(
            'paypal_order_status',
            $contents
        );
        $this->assertStringContainsString(
            'paypal_capture_status',
            $contents
        );
    }
}
