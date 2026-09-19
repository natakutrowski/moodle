<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h416_wallet_measurable_mount_test extends advanced_testcase {
    public function test_express_checkout_mount_is_measurable_while_stripe_probes(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );
        $this->assertIsString($css);

        $this->assertStringContainsString('commerce-checkout-express-wallets is-probing', $template);
        $this->assertStringContainsString('data-wallet-state="probing"', $template);
        $this->assertStringContainsString('visibility: visible;', $css);
        $this->assertStringContainsString('min-height: 72px;', $css);
    }

    public function test_availability_event_resolves_ready_or_unavailable_state(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        foreach ([
            "'availablepaymentmethodschange'",
            "'is-wallet-ready'",
            "'is-wallet-unavailable'",
            'section.dataset.walletState',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }
}
