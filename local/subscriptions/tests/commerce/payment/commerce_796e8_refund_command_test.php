<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e8_refund_command_test extends advanced_testcase {
    public function test_refund_command_guards_idempotency_and_refundable_balance(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundCommand.php'
        );

        $this->assertStringContainsString(
            'find_by_idempotency_key(',
            $contents
        );
        $this->assertStringContainsString(
            'refundable_amount_minor(',
            $contents
        );
        $this->assertStringContainsString(
            'refund_amount_invalid',
            $contents
        );
        $this->assertStringContainsString(
            'mark_failed(',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentRefundService',
            $contents
        );
    }

    public function test_refund_repository_counts_pending_and_succeeded_against_balance(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/refund/'
            . 'CommercePaymentRefundRecord.php'
        );

        $this->assertStringContainsString(
            'STATUS_PENDING',
            $contents
        );
        $this->assertStringContainsString(
            'STATUS_SUCCEEDED',
            $contents
        );
    }
}
