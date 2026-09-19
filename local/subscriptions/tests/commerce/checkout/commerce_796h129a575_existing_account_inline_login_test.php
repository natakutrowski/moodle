<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a575_existing_account_inline_login_test extends \advanced_testcase {
    public function test_existing_account_gate_requires_verified_locked_identity(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $source
        );
        self::assertStringContainsString(
            '$guestverificationstate?->is_locked() === true',
            $source
        );
        self::assertStringContainsString(
            "\$guestsession?->get_status() === 'existing_account'",
            $source
        );
    }

    public function test_inline_login_has_readonly_verified_email_and_password_only(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'data-existing-account-inline-login',
            $template
        );
        self::assertStringContainsString(
            'data-existing-account-verified-email',
            $template
        );
        self::assertStringContainsString(
            '{{embeddedloginemail}}',
            $template
        );
        self::assertStringContainsString(
            'type="password"',
            $template
        );
        self::assertStringContainsString(
            'name="password"',
            $template
        );
        self::assertStringContainsString(
            'name="username" value="{{embeddedloginusername}}"',
            $template
        );
        self::assertStringNotContainsString(
            'name="email" value="{{embeddedloginemail}}"',
            $template
        );
    }

    public function test_core_moodle_login_remains_authoritative_and_resumes_checkout(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $resume = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );

        self::assertStringContainsString(
            "new moodle_url('/login/index.php')",
            $checkout
        );
        self::assertStringContainsString(
            '$SESSION->wantsurl = $resumeurl->out(false);',
            $checkout
        );
        self::assertStringContainsString(
            "'from' => 'verified_identity_login'",
            $checkout
        );
        self::assertStringContainsString(
            "require_login();",
            $resume
        );
        self::assertStringContainsString(
            "\$guestsession->get_user_id() !== (int)\$USER->id",
            $resume
        );
        self::assertStringContainsString(
            'CommerceGuestCartTransferService::create()->transfer(',
            $resume
        );
        self::assertStringContainsString(
            "'checkout_source' => 'source'",
            $resume
        );
        self::assertStringContainsString(
            "'origin_return' => 'originreturn'",
            $resume
        );
    }

    public function test_existing_account_resume_reconciles_owned_products_before_checkout(): void {
        global $CFG;

        $resume = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );
        self::assertIsString($resume);

        self::assertStringContainsString(
            'CommerceAuthenticatedCartReconciliationService',
            $resume
        );
        self::assertStringContainsString(
            '->reconcile(',
            $resume
        );
        self::assertStringContainsString(
            "'authenticated_cart_items_removed'",
            $resume
        );
        self::assertStringContainsString(
            "unset(\$metadata['guest_cart_snapshot']);",
            $resume
        );
    }
}
