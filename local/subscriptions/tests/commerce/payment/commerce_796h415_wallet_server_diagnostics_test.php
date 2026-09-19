<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h415_wallet_server_diagnostics_test extends advanced_testcase {
    public function test_removed_wallet_diagnostic_endpoint_stays_removed_and_checkout_is_secret_free(): void {
        global $CFG;

        $endpoint =
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'wallet_diagnostics.php';

        $this->assertFileDoesNotExist($endpoint);

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "'publishable_key_present'",
            $checkout
        );
        $this->assertStringNotContainsString(
            "'secret_key'",
            $checkout
        );
        $this->assertStringNotContainsString(
            "'client_secret'",
            $checkout
        );
    }
}
