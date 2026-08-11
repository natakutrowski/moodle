<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityValidator;

final class commerce_795h52de_guest_resume_validation_test extends \advanced_testcase {
    public function test_identity_validator_normalises_valid_input(): void {
        $identity = CommerceGuestIdentityValidator::validate(
            '  CLIENT@Example.COM  ',
            "  Marie   Claire ",
            " D'Arc "
        );

        $this->assertSame('client@example.com', $identity['email']);
        $this->assertSame('Marie Claire', $identity['firstname']);
        $this->assertSame("D'Arc", $identity['lastname']);
    }

    public function test_identity_validator_rejects_invalid_email(): void {
        $this->expectException(\moodle_exception::class);
        CommerceGuestIdentityValidator::validate('not-an-email', 'Marie', 'Dupont');
    }

    public function test_identity_validator_rejects_empty_or_excessive_names(): void {
        try {
            CommerceGuestIdentityValidator::validate('client@example.com', '', 'Dupont');
            $this->fail('An empty first name must be rejected.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('commerce_guest_checkout_invalid_firstname', $exception->errorcode);
        }

        $this->expectException(\moodle_exception::class);
        CommerceGuestIdentityValidator::validate(
            'client@example.com',
            str_repeat('a', 101),
            'Dupont'
        );
    }

    public function test_existing_account_login_resumes_directly_to_checkout(): void {
        $root = dirname(__DIR__, 3);
        $guestcheckout = file_get_contents($root . '/commerce_checkout.php');
        $resume = file_get_contents($root . '/guest_checkout_resume.php');
        $cart = file_get_contents($root . '/cart.php');
        $template = file_get_contents($root . '/templates/checkout/page.mustache');

        $this->assertStringContainsString("'/local/subscriptions/guest_checkout_resume.php'", $guestcheckout);
        $this->assertStringContainsString("'/local/subscriptions/commerce_checkout.php'", $resume);
        $this->assertStringContainsString('CommerceGuestCartTransferService::create()->transfer', $resume);
        $this->assertStringContainsString("'/local/subscriptions/commerce_checkout.php'", $cart);
        $this->assertStringContainsString('maxlength="100"', $template);
        $this->assertStringContainsString('inputmode="email"', $template);
    }
}
