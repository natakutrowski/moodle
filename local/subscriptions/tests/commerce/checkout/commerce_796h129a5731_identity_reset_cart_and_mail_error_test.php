<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5731_identity_reset_cart_and_mail_error_test extends \advanced_testcase {
    public function test_identity_reset_restores_durable_cart_before_detaching_user(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityVerificationService.php'
        );
        $transfer = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartTransferService.php'
        );

        self::assertStringContainsString(
            "'guest_cart_snapshot'",
            $service
        );
        self::assertStringContainsString(
            '->restore_anonymous(',
            $service
        );
        self::assertStringContainsString(
            "'userid' => null",
            $service
        );
        self::assertStringContainsString(
            'public function restore_anonymous(',
            $transfer
        );
        self::assertStringContainsString(
            '$this->keys->resolve(0, $currency)',
            $transfer
        );
        self::assertStringContainsString(
            '$this->repository->delete(',
            $transfer
        );
    }

    public function test_mail_detail_exposes_full_delivery_error(): void {
        global $CFG;

        $view = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/mail/view.php'
        );

        self::assertStringContainsString(
            "\$record->lasterror",
            $view
        );
        self::assertStringContainsString(
            "'commerce_mail_last_error'",
            $view
        );
        self::assertStringContainsString(
            "'white-space:pre-wrap;'",
            $view
        );
    }

    public function test_otp_has_a_named_admin_presentation(): void {
        global $CFG;

        $presentation = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/mail/admin/'
            . 'CommerceMailAdminPresentation.php'
        );
        $fr = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            'CommerceMailType::GUEST_IDENTITY_OTP',
            $presentation
        );
        self::assertStringContainsString(
            'commerce_mail_type_guest_identity_otp',
            $fr
        );
    }
}
