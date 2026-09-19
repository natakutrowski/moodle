<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g7_payment_methods_cli_test extends advanced_testcase {
    public function test_cli_is_read_only_and_supports_strict_mode(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_payment_methods_routing.php'
        );

        $this->assertStringContainsString("'strict' => false", $contents);
        $this->assertStringContainsString(
            'Payment methods/routing certification: PASS',
            $contents
        );
        $this->assertStringContainsString(
            'This audit is read-only and never creates or executes a payment.',
            $contents
        );
    }
}
