<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a572_guest_identity_otp_service_test extends \advanced_testcase {

    public function test_otp_type_is_routable_and_registered_in_commerce_mail(): void {
        self::assertContains(
            \local_subscriptions\commerce\mail\CommerceMailType::GUEST_IDENTITY_OTP,
            \local_subscriptions\commerce\mail\CommerceMailType::routable()
        );
        self::assertTrue(
            \local_subscriptions\commerce\mail\CommerceMailRuntime::template_registry()->has(
                \local_subscriptions\commerce\mail\CommerceMailType::GUEST_IDENTITY_OTP
            )
        );
    }


    public function test_otp_challenge_stores_hash_not_raw_code(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityOtpChallengeService.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('password_hash(', $source);
        self::assertStringContainsString('PASSWORD_DEFAULT', $source);
        self::assertStringContainsString('password_verify($code, $hash)', $source);
        self::assertStringNotContainsString("'identity_otp_code'", $source);
    }


    public function test_otp_limits_are_explicit_and_bounded(): void {
        self::assertSame(600, \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityOtpChallengeService::CODE_TTL);
        self::assertSame(60, \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityOtpChallengeService::RESEND_DELAY);
        self::assertSame(5, \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityOtpChallengeService::MAX_SENDS_PER_WINDOW);
        self::assertSame(5, \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityOtpChallengeService::MAX_VERIFY_ATTEMPTS);
    }


    public function test_account_resolution_occurs_only_after_code_verification(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityOtpChallengeService.php'
        );
        self::assertIsString($source);
        global $CFG;

        $start = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/guest_identity_otp_start.php'
        );
        self::assertIsString($start);
        global $CFG;

        $verify = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/guest_identity_otp_verify.php'
        );
        self::assertIsString($verify);

        $verified = strpos($source, 'password_verify($code, $hash)');
        $identify = strpos($source, '$this->checkout->identify(');
        self::assertNotFalse($verified);
        self::assertNotFalse($identify);
        self::assertGreaterThan($verified, $identify);
        self::assertStringNotContainsString('record_exists', $start);
        self::assertStringContainsString("'requiresLogin'", $verify);
    }


    public function test_otp_endpoints_require_session_protection(): void {
        global $CFG;
        foreach (['ajax/guest_identity_otp_start.php', 'ajax/guest_identity_otp_verify.php'] as $relative) {
            $source = file_get_contents($CFG->dirroot . '/local/subscriptions/' . $relative);
            self::assertIsString($source);
            self::assertStringContainsString('require_sesskey();', $source);
            self::assertStringContainsString('local_subscriptions_guest_checkout_token', $source);
        }
    }

}
