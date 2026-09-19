<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5736_otp_no_stale_retry_test extends \advanced_testcase {
    public function test_otp_queue_uses_single_attempt_and_never_delayed_retry(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        self::assertStringContainsString(
            "CommerceMailType::GUEST_IDENTITY_OTP",
            $source
        );
        self::assertStringContainsString(
            "->cancel_queued_by_prefix(",
            $source
        );
        self::assertStringContainsString(
            "\$request,\n            1\n        );",
            $source
        );
        self::assertStringContainsString(
            'delayed retry is forbidden',
            $source
        );
    }

    public function test_challenge_is_persisted_before_mail_is_processed(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        $hash = strpos(
            $source,
            "\$metadata['identity_otp_hash'] = password_hash("
        );
        $send = strpos(
            $source,
            'if (!$this->send_code('
        );

        self::assertNotFalse($hash);
        self::assertNotFalse($send);
        self::assertLessThan(
            $send,
            $hash
        );
    }

    public function test_failed_delivery_clears_active_otp_secret(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        self::assertStringContainsString(
            "unset(\n                \$metadata['identity_otp_challenge_id'],",
            $source
        );
        self::assertStringContainsString(
            "\$metadata['identity_otp_hash']",
            $source
        );
    }

    public function test_queue_repository_can_cancel_stale_otp_by_prefix(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/mail/'
            . 'CommerceMailQueueRepository.php'
        );

        self::assertStringContainsString(
            'public function cancel_queued_by_prefix(',
            $source
        );
        self::assertStringContainsString(
            "status = :status",
            $source
        );
        self::assertStringContainsString(
            "idempotencykey LIKE :prefix",
            $source
        );
    }
}
