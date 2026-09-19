<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e12_alfa_refund_admin_and_i18n_test extends advanced_testcase {
    public function test_sales_refund_action_is_provider_capability_driven(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/index.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentRefundService',
            $contents
        );
        $this->assertStringContainsString(
            '$refundproviders',
            $contents
        );
        $this->assertStringNotContainsString(
            "pay.provider='stripe'",
            $contents
        );
    }

    public function test_refund_history_action_is_provider_contract_driven(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            'CommerceRefundHistoryCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            '$refundhistoryproviders',
            $contents
        );
    }

    public function test_currency_without_provider_warning_is_translated(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            $this->assertStringContainsString(
                'commerce_payment_architecture_warning_currency_without_provider',
                $contents
            );
        }
    }
}
