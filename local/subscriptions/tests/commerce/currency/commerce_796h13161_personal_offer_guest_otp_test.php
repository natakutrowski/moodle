<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13161_personal_offer_guest_otp_test extends \advanced_testcase {

    public function test_personal_offer_signed_link_is_treated_as_mailbox_possession_proof(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString('personal_offer_reserved_email', $checkout);
        self::assertStringContainsString("'identity_proof' => 'personal_offer_signed_link'", $checkout);
        self::assertStringContainsString("'personal_offer_mailbox_proof_at'", $checkout);
        self::assertStringContainsString('CommerceGuestIdentityVerificationState::locked_metadata(', $checkout);
    }


    public function test_personal_offer_uses_dedicated_missing_name_completion_and_not_generic_otp(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('$showguestidentity = false;', $checkout);
        self::assertStringContainsString("'personalofferneedsidentitycompletion'", $checkout);
        self::assertStringContainsString('data-personal-offer-identity-completion', $template);
        self::assertStringContainsString('data-personal-offer-identity-confirm', $template);
        self::assertStringContainsString('{{^personalofferbearerproof}}', $template);
    }


    public function test_reserved_personal_offer_identity_fields_remain_readonly_when_known(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        foreach (['guestemailreadonly', 'guestfirstnamereadonly', 'guestlastnamereadonly'] as $key) {
            self::assertStringContainsString($key, $template);
            self::assertStringContainsString("'" . $key . "' =>", $checkout);
        }
    }


    public function test_personal_offer_identity_confirmation_endpoint_is_exposed(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/guest_personal_offer_identity_confirm.php'
        );
        self::assertIsString($endpoint);

        self::assertStringContainsString('guest_personal_offer_identity_confirm.php', $checkout);
        self::assertStringContainsString('require_sesskey();', $endpoint);
        self::assertStringContainsString('personal_offer_reserved_email', $endpoint);
    }

}
