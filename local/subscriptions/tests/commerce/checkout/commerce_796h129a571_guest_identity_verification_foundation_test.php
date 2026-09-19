<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a571_guest_identity_verification_foundation_test extends \advanced_testcase {

    public function test_name_fields_are_optional_individually_and_frontend_requires_one_ready_name(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('data-identity-name', $template);
        self::assertStringContainsString('data-identity-name-status', $template);
        self::assertStringNotContainsString('data-required-name', $template);
        self::assertStringContainsString('const nameStates = () =>', $amd);
        self::assertStringContainsString('value.length >= 2', $amd);
        self::assertStringContainsString('nameStates().some(Boolean)', $amd);
    }


    public function test_server_validator_uses_same_one_of_two_names_contract(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityValidator.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$firstname === \'\'', $source);
        self::assertStringContainsString('$lastname === \'\'', $source);
        self::assertStringContainsString('($firstname === \'\' && $lastname === \'\')', $source);
        self::assertStringContainsString('commerce_guest_checkout_invalid_name_identity', $source);
    }


    public function test_verification_state_supports_editing_pending_and_locked_contract(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestIdentityVerificationState.php'
        );
        self::assertIsString($source);

        foreach (['is_editing', 'is_otp_pending', 'is_locked', 'get_verified_email'] as $method) {
            self::assertStringContainsString('function ' . $method, $source);
        }
    }

}
