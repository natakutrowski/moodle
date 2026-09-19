<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h432_wallet_amd_permanent_name_test extends advanced_testcase {
    public function test_checkout_uses_permanent_wallet_module_name(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "'local_subscriptions/checkout_express_wallets'",
            $checkout
        );
        $this->assertStringNotContainsString(
            'checkout_express_wallets_h418',
            $checkout
        );
    }

    public function test_amd_source_contains_no_h418_reference(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringNotContainsString('h418', strtolower($js));
        $this->assertStringContainsString('express.mount(mount);', $js);
        $this->assertStringContainsString(
            "'availablepaymentmethodschange'",
            $js
        );
    }
}
