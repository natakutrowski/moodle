<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e126_alfa_refund_reconciliation_test extends \advanced_testcase {

    public function test_alfa_provider_status_has_refund_aware_financial_settlement_rule(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/reconciliation/alfa/AlfaPaymentProviderStatus.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('public function is_refunded_state(): bool', $source);
        self::assertStringContainsString('public function is_financially_settled(): bool', $source);
        self::assertStringContainsString('$this->depositedamountminor', $source);
        self::assertStringContainsString('$this->refundedamountminor', $source);
        self::assertMatchesRegularExpression(
            '/amountminor\s*-\s*\$this->refundedamountminor/s',
            $source
        );
    }


    public function test_reconciliation_compares_net_deposit_after_refund(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/reconciliation/alfa/AlfaPaymentReconciliationService.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$expecteddepositedamountminor', $source);
        self::assertStringContainsString('$provider->refundedamountminor', $source);
        self::assertStringContainsString("'refund_amount_mismatch'", $source);
        self::assertStringContainsString('$provider->is_financially_settled()', $source);
    }


    public function test_completed_purchase_is_not_reopened_only_because_provider_event_is_refund_related(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/reconciliation/alfa/AlfaPaymentReconciliationService.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$alreadycomplete = in_array(', $source);
        self::assertStringContainsString('!$alreadycomplete', $source);
        self::assertStringContainsString("'provider_event_not_completed'", $source);
    }

}
