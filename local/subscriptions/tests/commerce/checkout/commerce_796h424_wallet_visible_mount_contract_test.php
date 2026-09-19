<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h424_wallet_visible_mount_contract_test extends advanced_testcase {
    public function test_wallet_mount_is_not_hidden_or_moved_offscreen_while_probing(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );
        $this->assertIsString($css);

        $this->assertStringContainsString('.commerce-checkout-express-wallets.is-probing', $css);
        $this->assertStringContainsString('visibility: visible;', $css);
        $this->assertStringNotContainsString('left: -10000px', $css);
    }

    public function test_current_amd_identity_is_preserved(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString("'local_subscriptions/checkout_express_wallets'", $checkout);
    }
}
