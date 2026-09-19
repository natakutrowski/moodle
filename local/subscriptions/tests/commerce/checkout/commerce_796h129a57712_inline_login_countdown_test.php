<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a57712_inline_login_countdown_test extends \advanced_testcase {
    public function test_inline_login_posts_to_checkout_ajax_not_core_login_page(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_existing_account_login.js'
        );

        self::assertStringContainsString(
            'data-login-url="{{embeddedloginajaxurl}}"',
            $template
        );
        self::assertStringContainsString(
            "event.preventDefault();",
            $amd
        );
        self::assertStringContainsString(
            "payload.code === 'invalid_credentials'",
            $amd
        );
        self::assertStringContainsString(
            'data-existing-account-login-feedback',
            $template
        );
    }

    public function test_ajax_authentication_uses_verified_session_account_only(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/guest_existing_account_login.php'
        );

        self::assertStringContainsString(
            "get_status() !== 'existing_account'",
            $source
        );
        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $source
        );
        self::assertStringContainsString(
            'authenticate_user_login(',
            $source
        );
        self::assertStringContainsString(
            'complete_user_login($user);',
            $source
        );
        self::assertStringNotContainsString(
            "required_param('username'",
            $source
        );
    }

    public function test_resend_countdown_is_customer_visible(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            'data-resend-label="{{guestotpresend}}"',
            $template
        );
        self::assertStringContainsString(
            "resend.textContent =",
            $amd
        );
        self::assertStringContainsString(
            "String(remaining)",
            $amd
        );
    }
}
