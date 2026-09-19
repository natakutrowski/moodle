<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12751_cta_wallet_gate_and_locked_consent_test extends \advanced_testcase {
    public function test_generic_cta_is_canonical_and_inactive_drivers_manage_visibility(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $inline = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );
        $alfa = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $sbp = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );

        self::assertStringContainsString('data-checkout-submit', $template);
        self::assertStringContainsString('submit.hidden = true;', $inline);
        self::assertStringContainsString('submit.hidden = true;', $alfa);
        self::assertStringContainsString('submit.hidden = true;', $sbp);
        self::assertStringContainsString('setPreparingSurface(false);', $sbp);
    }

    public function test_legal_card_uses_checkbox_then_lock_and_corner_check(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'commerce-checkout-legal__status-checkbox',
            $template
        );
        self::assertStringContainsString(
            'commerce-checkout-legal__status-lock',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-legal-check',
            $template
        );
    }

    public function test_express_checkout_remains_explorable_and_consent_is_enforced_on_confirm(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringNotContainsString(
            'commerce-checkout-express-wallets__consent-gate',
            $amd
        );
        self::assertStringNotContainsString(
            'mount.style.pointerEvents',
            $amd
        );
        self::assertStringContainsString(
            "express.on(\n            'confirm'",
            $amd
        );
        self::assertStringContainsString(
            "'[data-checkout-terms]'",
            $amd
        );
        self::assertStringContainsString(
            'event?.paymentFailed?.({',
            $amd
        );
    }
}
