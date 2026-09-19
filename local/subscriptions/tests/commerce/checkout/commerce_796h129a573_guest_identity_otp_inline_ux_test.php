<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a573_guest_identity_otp_inline_ux_test extends \advanced_testcase {

    public function test_checkout_exposes_inline_otp_and_identity_confirmation_contract(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        foreach ([
            'data-guest-identity-confirm',
            'data-guest-identity-otp',
            'data-guest-identity-otp-code',
            'autocomplete="one-time-code"',
            'inputmode="numeric"',
            'maxlength="6"',
            'data-otp-start-url="{{guestotpstarturl}}"',
        ] as $expected) {
            self::assertStringContainsString($expected, $template);
        }
        self::assertStringContainsString('guest_identity_otp_start.php', $checkout);
        self::assertStringContainsString('guest_identity_otp_verify.php', $checkout);
    }


    public function test_identity_confirm_issues_otp_and_six_digits_trigger_verification(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString("'[data-guest-identity-confirm]'", $amd);
        self::assertStringContainsString('void issueOtp(false);', $amd);
        self::assertStringContainsString('otpCode.value.length === 6', $amd);
        self::assertStringContainsString('void verifyOtp();', $amd);
    }


    public function test_verified_identity_is_readonly_until_explicit_reset(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        self::assertIsString($amd);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('input.readOnly = true;', $amd);
        self::assertStringContainsString("'is-identity-locked'", $amd);
        self::assertStringContainsString('data-guest-identity-reset', $template);
        self::assertStringContainsString('href="{{otheremailurl}}"', $template);
    }


    public function test_existing_account_after_verified_otp_triggers_controlled_reload(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('if (payload.requiresLogin) {', $amd);
        self::assertStringContainsString('window.location.reload();', $amd);
    }

}
