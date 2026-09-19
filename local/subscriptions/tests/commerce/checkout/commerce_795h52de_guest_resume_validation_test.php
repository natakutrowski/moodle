<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795h52de_guest_resume_validation_test extends \advanced_testcase {

    public function test_identity_validator_normalises_valid_input(): void {
        $identity = \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator::validate(
            '  CLIENT@Example.COM  ',
            '  Marie   Claire ',
            " D'Arc "
        );
        self::assertSame('client@example.com', $identity['email']);
        self::assertSame('Marie Claire', $identity['firstname']);
        self::assertSame("D'Arc", $identity['lastname']);
    }


    public function test_identity_validator_accepts_one_name_but_rejects_missing_identity_name(): void {
        $firstonly = \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator::validate(
            'client@example.com',
            'Marie',
            ''
        );
        self::assertSame('Marie', $firstonly['firstname']);
        self::assertSame('', $firstonly['lastname']);

        $lastonly = \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator::validate(
            'client@example.com',
            '',
            'Dupont'
        );
        self::assertSame('', $lastonly['firstname']);
        self::assertSame('Dupont', $lastonly['lastname']);

        $this->expectException(\moodle_exception::class);
        \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator::validate(
            'client@example.com',
            '',
            ''
        );
    }


    public function test_identity_validator_rejects_invalid_email(): void {
        $this->expectException(\moodle_exception::class);
        \local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator::validate(
            'not-an-email',
            'Marie',
            'Dupont'
        );
    }


    public function test_checkout_keeps_resume_and_input_hardening_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions';
        $guestcheckout = file_get_contents($root . '/commerce_checkout.php');
        $resume = file_get_contents($root . '/guest_checkout_resume.php');
        $template = file_get_contents($root . '/templates/checkout/page.mustache');
        self::assertIsString($guestcheckout);
        self::assertIsString($resume);
        self::assertIsString($template);
        self::assertStringContainsString("'/local/subscriptions/guest_checkout_resume.php'", $guestcheckout);
        self::assertStringContainsString('CommerceGuestCartTransferService::create()->transfer', $resume);
        self::assertStringContainsString('maxlength="100"', $template);
        self::assertStringContainsString('inputmode="email"', $template);
    }

}
