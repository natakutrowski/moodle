<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e123_refund_provider_error_chain_test extends advanced_testcase {
    public function test_refund_service_preserves_provider_error_chain(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundService.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentProviderException',
            $contents
        );
        $this->assertStringContainsString(
            'provider_failure_message(',
            $contents
        );
        $this->assertStringContainsString(
            '$current->getPrevious()',
            $contents
        );
        $this->assertStringContainsString(
            "implode(' — ', \$messages)",
            $contents
        );

        // The generic fallback may remain only as the last-resort helper text;
        // provider exceptions must no longer be replaced by it directly.
        $this->assertStringContainsString(
            '$exception->get_provider_code()',
            $contents
        );
    }
}
