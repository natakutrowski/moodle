<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e9_refund_history_ui_test extends advanced_testcase {
    public function test_purchase_view_reads_native_refund_history(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        $this->assertStringContainsString(
            'find_for_payment($payment->id)',
            $contents
        );
        $this->assertStringContainsString(
            'refundable_amount_minor(',
            $contents
        );
        $this->assertStringContainsString(
            'commerce_refund_history_title',
            $contents
        );
        $this->assertStringContainsString(
            'get_provider_refund_id()',
            $contents
        );
    }

    public function test_refund_history_ui_does_not_require_a_new_schema_beyond_refund_foundation(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );
        $this->assertIsString($version);
        preg_match('/\$plugin->version\s*=\s*(\d+);/', $version, $matches);
        $this->assertNotEmpty($matches);
        $this->assertGreaterThanOrEqual(2026082301, (int)$matches[1]);
    }
}
