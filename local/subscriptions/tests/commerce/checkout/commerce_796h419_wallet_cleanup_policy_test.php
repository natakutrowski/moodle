<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h419_wallet_cleanup_policy_test extends advanced_testcase {
    public function test_customer_template_keeps_real_wallet_surface_without_debug_ui(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        $this->assertStringContainsString('data-checkout-express-wallet-section', $template);
        $this->assertStringContainsString('data-checkout-express-wallet-element', $template);
        $this->assertStringNotContainsString('data-wallet-debug-log', $template);
        $this->assertStringNotContainsString('Diagnostic Apple Pay / Google Pay', $template);
    }

    public function test_all_current_stripe_express_rails_are_config_gated(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach (['apple_pay', 'google_pay', 'link', 'klarna'] as $method) {
            $this->assertStringContainsString("allowed(config, '" . $method . "')", $js);
        }
    }

    public function test_wallet_availability_controls_final_state_not_html_hidden_toggling(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString("'availablepaymentmethodschange'", $js);
        $this->assertStringContainsString('setResolvedState(', $js);
        $this->assertStringNotContainsString('section.hidden = !visible;', $js);
    }
}
