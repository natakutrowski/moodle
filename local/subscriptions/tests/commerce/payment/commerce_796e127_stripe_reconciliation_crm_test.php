<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e127_stripe_reconciliation_crm_test extends advanced_testcase {
    public function test_stripe_reconciliation_page_uses_existing_safe_engine(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/reconcile_stripe.php'
        );

        $this->assertStringContainsString(
            'StripePaymentReconciliationService::create($DB)',
            $contents
        );
        $this->assertStringContainsString(
            'inspect_purchase_reference(',
            $contents
        );
        $this->assertStringContainsString(
            '$inspection->reconcilable',
            $contents
        );
        $this->assertStringContainsString(
            'require_capability(',
            $contents
        );
        $this->assertStringContainsString(
            'reconcile_payment(',
            $contents
        );
    }

    public function test_purchase_view_exposes_stripe_reconciliation_panel(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            '$summary->provider === Provider::STRIPE',
            $contents
        );
        $this->assertStringContainsString(
            'reconcile_stripe.php',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_stripe_crm_verify',
            $contents
        );
    }

    public function test_stripe_service_can_inspect_purchase_reference(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/reconciliation/stripe/'
            . 'StripePaymentReconciliationService.php'
        );

        $this->assertStringContainsString(
            'inspect_purchase_reference(',
            $contents
        );
        $this->assertStringContainsString(
            'find_for_purchase(',
            $contents
        );
    }
}
