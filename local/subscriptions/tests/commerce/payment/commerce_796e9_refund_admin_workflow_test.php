<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e9_refund_admin_workflow_test extends advanced_testcase {
    public function test_purchase_payment_summary_exposes_native_payment_id(): void {
        global $CFG;

        $summary = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/purchase/readmodel/'
            . 'CommercePurchasePaymentSummary.php'
        );
        $repository = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/purchase/readmodel/'
            . 'CommercePurchaseReadRepository.php'
        );

        $this->assertStringContainsString(
            'public readonly ?int $id = null',
            $summary
        );
        $this->assertStringContainsString(
            '(int)$payment->id',
            $repository
        );
    }

    public function test_refund_screen_requires_manage_subscription_capability_and_confirmation(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/refund.php'
        );

        $this->assertStringContainsString(
            'AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS)',
            $contents
        );
        $this->assertStringContainsString(
            "'confirmrefund'",
            $contents
        );
        $this->assertStringContainsString(
            'PARAM_BOOL',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentRefundCommand',
            $contents
        );
        $this->assertStringContainsString(
            'Provider::get(',
            $contents
        );
        $this->assertStringNotContainsString(
            'Provider::label(',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceCurrencyAmount::from_major_input',
            $contents
        );
        $this->assertStringContainsString(
            "'admin-refund-' . \$paymentid . '-' . \$token",
            $contents
        );
    }

    public function test_purchase_view_only_exposes_refund_for_supported_paid_payments(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            '$refundservice->is_supported($payment->provider)',
            $contents
        );
        $this->assertStringContainsString(
            '$remainingminor > 0',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_refund_action',
            $contents
        );
    }
}
