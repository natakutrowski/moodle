<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5773_guest_identity_verification_certification_test extends \advanced_testcase {
    public function test_identity_state_machine_is_explicit_and_locked_after_otp(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityVerificationState.php'
        );

        self::assertStringContainsString("EDITING = 'editing'", $source);
        self::assertStringContainsString("OTP_PENDING = 'otp_pending'", $source);
        self::assertStringContainsString("IDENTITY_LOCKED = 'identity_locked'", $source);
    }

    public function test_otp_is_hashed_bounded_and_non_retryable(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        self::assertStringContainsString('password_hash(', $source);
        self::assertStringContainsString('password_verify(', $source);
        self::assertStringNotContainsString('identity_otp_code', $source);
        self::assertStringContainsString('CODE_TTL = 600', $source);
        self::assertStringContainsString('RESEND_DELAY = 60', $source);
        self::assertStringContainsString('MAX_SENDS_PER_WINDOW = 5', $source);
        self::assertStringContainsString('MAX_VERIFY_ATTEMPTS = 5', $source);
        self::assertStringContainsString('cancel_queued_by_prefix(', $source);
        self::assertStringContainsString("\$request,\n            1\n        );", $source);
    }

    public function test_account_existence_is_not_disclosed_before_valid_otp(): void {
        global $CFG;

        $legacy = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/check_email.php'
        );
        $otp = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        self::assertStringNotContainsString('record_exists', $legacy);
        self::assertStringNotContainsString("'exists'", $legacy);

        $verify = strpos($otp, 'password_verify(');
        $identify = strpos($otp, '$this->checkout->identify(');

        self::assertNotFalse($verify);
        self::assertNotFalse($identify);
        self::assertGreaterThan($verify, $identify);
    }

    public function test_payment_execution_is_server_gated_by_verified_identity(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        $gate = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestPaymentGate.php'
        );

        self::assertStringContainsString(
            'CommerceGuestPaymentGate::is_ready(',
            $action
        );
        self::assertStringContainsString(
            'guest_identity_verification_required',
            $action
        );
        self::assertStringNotContainsString(
            'CommerceGuestCheckoutService::create()->identify(',
            $action
        );
        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $gate
        );
    }

    public function test_existing_account_login_uses_verified_session_identity_and_inline_errors(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/ajax/guest_existing_account_login.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_existing_account_login.js'
        );

        self::assertStringContainsString(
            "get_status() !== 'existing_account'",
            $endpoint
        );
        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $endpoint
        );
        self::assertStringNotContainsString(
            "required_param('username'",
            $endpoint
        );
        self::assertStringContainsString(
            'authenticate_user_login(',
            $endpoint
        );
        self::assertStringContainsString(
            'complete_user_login($user);',
            $endpoint
        );
        self::assertStringContainsString(
            'event.preventDefault();',
            $amd
        );
        self::assertStringContainsString(
            'invalid_credentials',
            $amd
        );
    }

    public function test_authenticated_cart_is_reconciled_through_canonical_cart_rules(): void {
        global $CFG;

        $reconcile = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceAuthenticatedCartReconciliationService.php'
        );
        $ownership = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/ownership/'
            . 'CommerceStorefrontOwnershipResolver.php'
        );
        $resume = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );

        self::assertStringContainsString('->add_product(', $reconcile);
        self::assertStringNotContainsString(
            'CommerceStorefrontOwnershipResolver',
            $reconcile
        );

        self::assertStringContainsString('owns_native_grant', $ownership);
        self::assertStringContainsString('owns_native_purchase', $ownership);
        self::assertStringContainsString('owns_legacy_digital_product', $ownership);
        self::assertStringContainsString('owns_legacy_plan', $ownership);

        self::assertStringContainsString(
            "unset(\$metadata['guest_cart_snapshot']);",
            $resume
        );
    }

    public function test_otp_resend_countdown_is_visible_to_customer(): void {
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
            'resend.textContent =',
            $amd
        );
        self::assertStringContainsString(
            'String(remaining)',
            $amd
        );
    }
}
