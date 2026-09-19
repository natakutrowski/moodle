<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h429_wallet_lint_cleanup_test extends advanced_testcase {
    public function test_removed_diagnostics_do_not_leave_dead_js_contracts(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        $this->assertStringContainsString(
            'const loadStripe = () =>',
            $js
        );
        $this->assertStringNotContainsString(
            "express.on(\n            'ready'",
            $js
        );
        $this->assertStringContainsString(
            '// Stripe Express Checkout is optional; keep the standard checkout available.',
            $js
        );
    }

    public function test_functional_wallet_lifecycle_remains_present(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        foreach ([
            "'availablepaymentmethodschange'",
            "'confirm'",
            'express.mount(mount);',
            'await elements.submit();',
            '.confirmPayment({',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
