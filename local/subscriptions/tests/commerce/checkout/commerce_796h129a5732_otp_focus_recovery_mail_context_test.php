<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5732_otp_focus_recovery_mail_context_test extends \advanced_testcase {
    public function test_otp_transport_establishes_page_context_for_interactive_ajax_delivery(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/mail/'
            . 'MoodleCommerceMailTransport.php'
        );

        self::assertStringContainsString('global $PAGE;', $source);
        self::assertStringContainsString(
            '$PAGE->set_context(\\context_system::instance());',
            $source
        );
    }

    public function test_current_guest_session_cannot_be_used_as_its_own_recovery_source(): void {
        global $CFG;

        $recovery = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceUnfinishedGuestCheckoutRecoveryService.php'
        );
        $provisioner = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestAccountProvisioner.php'
        );

        self::assertStringContainsString(
            'find_source_session(int $userid, ?int $excludeid = null)',
            $recovery
        );
        self::assertStringContainsString(
            '$session->get_id() === $excludeid',
            $recovery
        );
        self::assertStringContainsString(
            '$session->get_id()',
            $provisioner
        );
    }

    public function test_identity_auto_issue_happens_on_focus_exit_not_typing_timer(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringNotContainsString('issueTimer', $amd);
        self::assertStringNotContainsString('() => issueOtp(false),\n                        850', $amd);
        self::assertStringContainsString("'blur',", $amd);
        self::assertStringContainsString('event.relatedTarget', $amd);
        self::assertStringContainsString('void issueOtp(false);', $amd);
        self::assertStringContainsString('data-guest-identity-confirm', $template);
    }
}
