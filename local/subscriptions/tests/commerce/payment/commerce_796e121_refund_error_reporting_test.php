<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e121_refund_error_reporting_test extends advanced_testcase {
    public function test_refund_page_does_not_mask_provider_errors_as_amount_errors(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/refund.php'
        );

        $amountcatch = strpos(
            $contents,
            "'commerce_refund_invalid_amount'"
        );
        $command = strpos(
            $contents,
            'new CommercePaymentRefundCommand('
        );
        $providerfailure = strrpos(
            $contents,
            "'commerce_refund_failed'"
        );

        $this->assertNotFalse($amountcatch);
        $this->assertNotFalse($command);
        $this->assertNotFalse($providerfailure);
        $this->assertLessThan($command, $amountcatch);
        $this->assertGreaterThan($command, $providerfailure);

        // Do not depend on the exact concatenation/format of the log line.
        $this->assertStringContainsString(
            '[commerce][refund]',
            $contents
        );
    }
}
