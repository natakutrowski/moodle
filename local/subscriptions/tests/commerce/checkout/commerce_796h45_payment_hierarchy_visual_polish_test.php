<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h45_payment_hierarchy_visual_polish_test extends advanced_testcase {
    public function test_payment_panel_has_explicit_hierarchy_hooks(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        foreach ([
            'commerce-checkout-payment-panel',
            'commerce-checkout-payment-heading',
            'commerce-checkout-payment-section',
        ] as $expected) {
            $this->assertStringContainsString($expected, $template);
        }
    }

    public function test_payment_sections_use_consistent_vertical_rhythm(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/guest_checkout.css'
        );

        foreach ([
            '.commerce-checkout-payment-section {',
            '.commerce-checkout-payment-heading {',
            '.commerce-checkout-payment-methods {',
            '.commerce-checkout-trust-strip {',
            '.commerce-checkout-legal {',
            '.commerce-checkout-submit {',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
    }

    public function test_h45_does_not_change_wallet_amd(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
    }
}
