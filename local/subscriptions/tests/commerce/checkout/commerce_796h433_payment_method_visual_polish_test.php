<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h433_payment_method_visual_polish_test extends advanced_testcase {
    public function test_payment_method_choices_have_compact_responsive_presentation(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        foreach ([
            '.commerce-checkout-payment-methods > .d-grid',
            'grid-template-columns: repeat(2, minmax(0, 1fr));',
            '.commerce-checkout-payment-methods .commerce-checkout-provider',
            'min-height: 4.25rem;',
            '@media (max-width: 575.98px)',
            'grid-template-columns: 1fr;',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
    }

    public function test_phase_does_not_modify_wallet_amd_contract(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringContainsString(
            "'availablepaymentmethodschange'",
            $js
        );
        $this->assertStringContainsString(
            'express.mount(mount);',
            $js
        );
    }
}
