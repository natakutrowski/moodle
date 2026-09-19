<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5737_reuse_provisional_after_identity_reset_test extends \advanced_testcase {
    public function test_reset_preserves_owned_provisional_user_for_same_checkout_reuse(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityVerificationService.php'
        );

        self::assertStringContainsString(
            "\$session->get_status() === 'provisional'",
            $source
        );
        self::assertStringContainsString(
            "'identity_reset_provisional_userid'",
            $source
        );
    }

    public function test_provisioner_reuses_exact_suspended_checkout_user_after_email_change(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestAccountProvisioner.php'
        );

        self::assertStringContainsString(
            "'identity_reset_provisional_userid'",
            $source
        );
        self::assertStringContainsString(
            "'checkout_'",
            $source
        );
        self::assertStringContainsString(
            '(int)$owned->confirmed === 0',
            $source
        );
        self::assertStringContainsString(
            '(int)$owned->suspended === 1',
            $source
        );
        self::assertStringContainsString(
            'user_update_user(',
            $source
        );
        self::assertStringContainsString(
            "'same_checkout_provisional_reuse'",
            $source
        );
    }

    public function test_reuse_clears_one_shot_reset_marker(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestAccountProvisioner.php'
        );

        self::assertStringContainsString(
            "unset(\n                    \$metadata['identity_reset_provisional_userid']",
            $source
        );
    }
}
