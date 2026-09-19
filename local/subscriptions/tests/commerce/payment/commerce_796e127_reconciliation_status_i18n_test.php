<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e127_reconciliation_status_i18n_test extends advanced_testcase {
    public function test_alfa_reconciliation_uses_presentation_labels_for_all_visible_statuses(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php'
        );

        $this->assertStringContainsString(
            'CommercePurchasePresentation::technical_status_label',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePurchasePresentation::commercial_status_label',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentReconciliationPresentation::alfa_order_status',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentReconciliationPresentation::alfa_payment_state',
            $contents
        );
    }

    public function test_provider_status_strings_exist_in_three_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            foreach ([
                'commerce_reconciliation_alfa_order_status_4',
                'commerce_reconciliation_alfa_status_refunded',
                'commerce_reconciliation_stripe_checkout_status_complete',
                'commerce_reconciliation_stripe_payment_status_paid',
            ] as $key) {
                $this->assertStringContainsString(
                    $key,
                    $contents
                );
            }
        }
    }
}
