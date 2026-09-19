<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f5_refund_aggregate_total_test extends advanced_testcase {
    public function test_refund_import_subtracts_known_individual_refunds_from_provider_total(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundImportService.php'
        );

        $this->assertStringContainsString(
            "['aggregate_total']",
            $contents
        );
        $this->assertStringContainsString(
            '$knownminor',
            $contents
        );
        $this->assertStringContainsString(
            '$missingminor',
            $contents
        );
        $this->assertStringContainsString(
            "'provider_total_minor'",
            $contents
        );
    }
}
