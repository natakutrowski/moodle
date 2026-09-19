<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h438_mobile_payment_card_compaction_test extends advanced_testcase {
    public function test_network_logos_are_hidden_on_small_screens(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            '@media (max-width: 575.98px)',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-checkout-payment-method__brands',
            $css
        );
        $this->assertStringContainsString(
            'display: none;',
            $css
        );
    }

    public function test_mobile_payment_card_keeps_readable_text_column(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            'grid-template-columns: auto 2.6rem minmax(0, 1fr);',
            $css
        );
        $this->assertStringContainsString(
            'min-height: 0;',
            $css
        );
    }
}
