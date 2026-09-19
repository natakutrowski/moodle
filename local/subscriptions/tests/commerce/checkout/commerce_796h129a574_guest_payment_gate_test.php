<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\guest\CommerceGuestPaymentGate;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a574_guest_payment_gate_test extends \advanced_testcase {
    public function test_payment_gate_requires_locked_verified_guest_identity(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestPaymentGate.php'
        );

        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $source
        );
        self::assertStringContainsString(
            ')->is_locked();',
            $source
        );
        self::assertStringContainsString(
            '$session->get_user_id() === null',
            $source
        );
    }

    public function test_checkout_action_has_server_side_gate_before_provider_execution(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        $gate = strpos(
            $source,
            '!CommerceGuestPaymentGate::is_ready('
        );
        $runtime = strpos(
            $source,
            'CommerceCheckoutContext('
        );

        self::assertNotFalse($gate);
        self::assertNotFalse($runtime);
        self::assertLessThan(
            $runtime,
            $gate
        );
        self::assertStringContainsString(
            "'guest_identity_verification_required'",
            $source
        );
        self::assertStringNotContainsString(
            'CommerceGuestCheckoutService::create()->identify(',
            $source
        );
    }

    public function test_checkout_renders_visible_but_disabled_payment_controls_for_unverified_guest(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'data-guest-payment-locked=',
            $template
        );
        self::assertStringContainsString(
            'data-guest-payment-gate-message',
            $template
        );
        self::assertStringContainsString(
            'data-guest-payment-control',
            $template
        );
        self::assertStringContainsString(
            '{{#guestpaymentlocked}}disabled{{/guestpaymentlocked}}',
            $template
        );
    }

    public function test_otp_success_unlocks_payment_without_reload(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            'const setGuestPaymentGate = (',
            $amd
        );
        self::assertStringContainsString(
            "form.dataset.guestPaymentLocked =",
            $amd
        );
        self::assertStringContainsString(
            "setGuestPaymentGate(\n            form,\n            false\n        );",
            $amd
        );
        self::assertStringContainsString(
            "'campusfr:guest-payment-gate-change'",
            $amd
        );
    }

    public function test_express_wallet_rejects_click_while_guest_payment_gate_is_locked(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "form.dataset.guestPaymentLocked === '1'",
            $amd
        );
        self::assertStringContainsString(
            'event.reject();',
            $amd
        );
    }
}
