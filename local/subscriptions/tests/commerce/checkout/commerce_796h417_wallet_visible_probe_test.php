<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h417_wallet_visible_probe_test extends advanced_testcase {
    public function test_probe_remains_visually_measurable_without_customer_debug_panel(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );
        $this->assertIsString($css);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        $this->assertStringContainsString('.commerce-checkout-express-wallets.is-probing', $css);
        $this->assertStringContainsString('visibility: visible;', $css);
        $this->assertStringContainsString('data-checkout-express-wallet-loading', $template);
        $this->assertStringContainsString('{{expresswalletprobing}}', $template);
        $this->assertStringNotContainsString('data-wallet-debug-log', $template);
    }

    public function test_probe_has_bounded_unavailable_fallback(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString('12000', $js);
        $this->assertStringContainsString("'is-wallet-unavailable'", $js);
        $this->assertStringContainsString("section.dataset.walletState =", $js);
    }
}
