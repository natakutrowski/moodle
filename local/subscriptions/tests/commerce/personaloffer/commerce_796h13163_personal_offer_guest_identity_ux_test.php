<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13163_personal_offer_guest_identity_ux_test extends \advanced_testcase {
    public function test_personal_offer_never_uses_generic_guest_identity_component(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        self::assertStringContainsString('H13.1.6.3: Personal Offers never render the generic Guest Checkout', $source);
        self::assertStringContainsString('$showguestidentity = false;', $source);
    }

    public function test_personal_offer_uses_one_dedicated_name_completion_ui(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        self::assertStringContainsString('data-personal-offer-identity-completion', $template);
        self::assertStringContainsString('data-personal-offer-identity-confirm', $template);
        self::assertStringContainsString('personal-offer-firstname', $template);
        self::assertStringContainsString('personal-offer-lastname', $template);
    }

    public function test_personal_offer_completion_uses_signed_link_endpoint_not_otp(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js');
        $start = strpos($js, 'const initialisePersonalOfferIdentityCompletion');
        self::assertNotFalse($start);
        $block = substr($js, $start, 5000);
        self::assertStringContainsString('personalOfferConfirmUrl', $block);
        self::assertStringNotContainsString('guest_identity_otp_start.php', $block);
    }
}
