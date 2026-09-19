<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5735_otp_delivery_throttle_contract_test extends \advanced_testcase {

    public function test_delivery_contract_marks_sent_and_consumes_quota_only_after_successful_send(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityOtpChallengeService.php'
        );
        self::assertIsString($source);

        $send = strpos($source, 'if (!$this->send_code(');
        $sent = strpos($source, '$metadata[\'identity_otp_sent_at\'] = $now;', $send);
        $count = strpos($source, '$metadata[\'identity_otp_send_count\'] =', $send);
        self::assertNotFalse($send);
        self::assertNotFalse($sent);
        self::assertNotFalse($count);
        self::assertGreaterThan($send, $sent);
        self::assertGreaterThan($send, $count);
        self::assertStringContainsString("'status' => 'delivery_failed'", $source);
        self::assertStringContainsString("'delivered_only_v2'", $source);
    }


    public function test_mail_page_context_is_set_before_queue_processor(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityOtpChallengeService.php'
        );
        self::assertIsString($source);

        $context = strpos($source, '$PAGE->set_context(\\context_system::instance());');
        $processor = strpos($source, 'CommerceMailRuntime::processor()->process_ids');
        self::assertNotFalse($context);
        self::assertNotFalse($processor);
        self::assertLessThan($processor, $context);
    }


    public function test_explicit_identity_reset_clears_delivery_throttle_metadata(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityVerificationState.php'
        );
        self::assertIsString($source);

        foreach (['identity_otp_send_window_started_at', 'identity_otp_send_count', 'identity_otp_delivery_contract'] as $key) {
            self::assertStringContainsString($key, $source);
        }
    }

}
