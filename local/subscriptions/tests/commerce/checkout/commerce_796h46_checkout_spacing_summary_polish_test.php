<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h46_checkout_spacing_summary_polish_test extends advanced_testcase {
    public function test_payment_column_restores_breathing_room(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        foreach ([
            '.commerce-checkout-payment-section {',
            'margin-top: 1.15rem;',
            '.commerce-checkout-payment-methods {',
            'margin-top: 1.05rem;',
            '.commerce-checkout-legal {',
            'margin-top: 1.2rem;',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
    }

    public function test_order_summary_has_stronger_total_hierarchy(): void {
        global $CFG;

        $guestcss = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );
        $storecss = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertStringContainsString(
            '.commerce-checkout__summary .commerce-checkout__grand-total',
            $guestcss
        );
        $this->assertStringContainsString(
            'font-weight: 700;',
            $guestcss
        );
        $this->assertStringContainsString(
            '/* H4.6 — checkout summary visual hierarchy. */',
            $storecss
        );
    }

    public function test_h46_does_not_modify_wallet_amd(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
    }
}
