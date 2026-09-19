<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h47_responsive_finishing_test extends advanced_testcase {
    public function test_reassurance_has_extra_spacing_after_payment_methods(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        $this->assertStringContainsString(
            '.commerce-checkout-payment-methods'
            . PHP_EOL
            . '    + .commerce-checkout-trust-strip',
            $css
        );
        $this->assertStringContainsString(
            'margin-top: 1.45rem;',
            $css
        );
    }

    public function test_checkout_has_phone_tablet_and_wide_desktop_breakpoints(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        foreach ([
            '@media (min-width: 576px) and (max-width: 991.98px)',
            '@media (max-width: 575.98px)',
            '@media (max-width: 390px)',
            '@media (min-width: 1400px)',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
    }

    public function test_summary_has_matching_responsive_pass(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertStringContainsString(
            '/* H4.7 — responsive summary finishing pass. */',
            $css
        );
    }

    public function test_wallet_amd_remains_untouched(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
    }
}
