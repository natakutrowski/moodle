<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h43_wallet_visual_polish_test extends advanced_testcase {
    public function test_wallet_markup_is_minimal(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            'data-checkout-express-wallet-element',
            $template
        );
        $this->assertStringContainsString(
            'commerce-checkout-express-wallets__divider',
            $template
        );
        $this->assertStringContainsString(
            '{{checkoutor}}',
            $template
        );

        foreach ([
            'commerce-checkout-express-wallets__title',
            'data-checkout-express-wallet-probe',
            'expresswallethelp',
            'expresswalletseparator',
            'Diagnostic Apple Pay / Google Pay',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $template
            );
        }
    }

    public function test_visual_phase_keeps_validated_wallet_state_classes(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        foreach ([
            '.commerce-checkout-express-wallets.is-probing',
            '.commerce-checkout-express-wallets.is-wallet-ready',
            '.commerce-checkout-express-wallets.is-wallet-unavailable',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $css
            );
        }
    }

    public function test_checkout_supplies_local_or_label(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "'commerce_checkout_or'",
            $checkout
        );
    }
}
