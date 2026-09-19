<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e126_alfa_refund_reconciliation_ui_test extends advanced_testcase {
    public function test_alfa_reconciliation_ui_displays_refund_specific_checks(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php'
        );

        $this->assertStringContainsString(
            'commerce_alfa_crm_refunded_amount',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_alfa_crm_check_provider_refund_settled',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_alfa_crm_check_deposited_after_refund',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_alfa_crm_check_refund_amount',
            $contents
        );
    }
}
