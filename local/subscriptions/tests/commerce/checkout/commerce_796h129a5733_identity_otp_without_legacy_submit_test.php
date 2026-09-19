<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5733_identity_otp_without_legacy_submit_test extends \advanced_testcase {
    public function test_identity_initialisation_does_not_require_removed_checkout_submit(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringNotContainsString(
            "|| !(submit instanceof HTMLButtonElement)\n    ) {\n        return;",
            $amd
        );
        self::assertStringContainsString(
            'if (submit instanceof HTMLButtonElement) {',
            $amd
        );
    }

    public function test_identity_confirm_and_blur_still_trigger_otp_issue(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            "confirmIdentity?.addEventListener(",
            $amd
        );
        self::assertStringContainsString(
            "'click',",
            $amd
        );
        self::assertStringContainsString(
            "void issueOtp(false);",
            $amd
        );
        self::assertStringContainsString(
            "'blur',",
            $amd
        );
        self::assertStringContainsString(
            'event.relatedTarget',
            $amd
        );
    }

    public function test_checkout_action_card_model_does_not_depend_on_generic_submit(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'data-guest-identity-confirm',
            $template
        );
        self::assertStringContainsString(
            'data-payment-action-card',
            $template
        );
    }
}
