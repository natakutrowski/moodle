<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h421_wallet_module_cleanup_test extends advanced_testcase {
    public function test_checkout_uses_current_stable_wallet_module_name(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString("'local_subscriptions/checkout_express_wallets'", $checkout);
        $this->assertStringNotContainsString("'local_subscriptions/checkout_express_payments'", $checkout);
    }

    public function test_wallet_source_has_current_stable_filename(): void {
        global $CFG;
        $this->assertFileExists(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertFileDoesNotExist(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_payments.js'
        );
    }
}
