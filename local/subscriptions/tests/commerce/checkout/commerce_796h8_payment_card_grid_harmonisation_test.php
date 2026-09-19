<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h8_payment_card_grid_harmonisation_test extends advanced_testcase {
    public function test_desktop_payment_cards_share_fixed_visual_columns(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        foreach ([
            '1.15rem',
            '2.75rem',
            'minmax(0, 1fr)',
            '5.75rem',
            'column-gap: .75rem;',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $css
            );
        }
    }

    public function test_provider_icons_share_common_visual_box(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            'width: 2.75rem;',
            $css
        );
        $this->assertStringContainsString(
            'height: 2.35rem;',
            $css
        );
        $this->assertStringContainsString(
            'max-height: 2rem;',
            $css
        );
    }

    public function test_paypal_link_and_klarna_wordmarks_share_visual_height_contract(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        foreach ([
            '.commerce-checkout-payment-method__brands--paypal img',
            '.commerce-checkout-payment-method__brands--link img',
            '.commerce-checkout-payment-method__brands--klarna img',
            'max-height: 1.45rem;',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $css
            );
        }
    }

    public function test_mobile_keeps_three_columns_and_hides_wordmarks(): void {
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
            'display: none;',
            $css
        );
    }
}
