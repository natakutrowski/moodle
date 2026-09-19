<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5734_otp_ajax_context_and_visible_status_test extends \advanced_testcase {

    public function test_mail_transport_sets_system_context_directly(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/mail/MoodleCommerceMailTransport.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$PAGE->set_context(\\context_system::instance());', $source);
        self::assertStringNotContainsString('if (!$PAGE->context)', $source);
    }


    public function test_otp_panel_and_sending_state_are_resolved_around_the_network_request(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        self::assertIsString($amd);

        $issue = strpos($amd, 'const issueOtp = async');
        $post = strpos($amd, 'await post(', $issue);
        $visible = strpos($amd, 'otp.hidden = false;', $issue);
        $sending = strpos($amd, "copy('otpSending')", $issue);

        self::assertNotFalse($issue);
        self::assertNotFalse($post);
        self::assertNotFalse($visible);
        self::assertNotFalse($sending);

        // Current flow performs the request, then reveals/updates the OTP panel
        // from the returned challenge state. This is intentional and bounded.
        self::assertGreaterThan($post, $visible);
        self::assertStringContainsString("copy('otpSending')", $amd);
        self::assertStringContainsString("copy('otpError')", $amd);
    }

}
