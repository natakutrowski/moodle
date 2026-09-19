<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5741_locked_express_wallet_visual_test extends \advanced_testcase {
    public function test_locked_express_wallets_are_dimmed_and_show_not_allowed_cursor(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            '.commerce-checkout-express-wallets.is-guest-payment-locked {',
            $css
        );
        self::assertStringContainsString(
            'cursor: not-allowed;',
            $css
        );
        self::assertStringContainsString(
            'opacity: .42;',
            $css
        );
        self::assertStringContainsString(
            '> * {',
            $css
        );
        self::assertStringContainsString(
            'pointer-events: none;',
            $css
        );
    }
}
