<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e125_refund_aggregate_sync_test extends advanced_testcase {
    public function test_aggregate_provider_refund_updates_in_place(): void {
        global $CFG;

        $repository = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundRepository.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundImportService.php'
        );

        $this->assertStringContainsString(
            "\$result->get_metadata()['aggregate']",
            $repository
        );
        $this->assertStringContainsString(
            '$record->amountminor =',
            $repository
        );
        $this->assertStringContainsString(
            '$beforeamount',
            $service
        );
        $this->assertStringContainsString(
            '$synchronized->get_amount_minor()',
            $service
        );
    }
}
