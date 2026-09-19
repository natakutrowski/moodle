<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_guest_checkout_inline_login_test extends \advanced_testcase {

    public function test_existing_account_login_is_rendered_inside_checkout_after_verified_identity(): void {
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

        self::assertStringContainsString('get_login_token()', $checkout);
        self::assertStringContainsString('$SESSION->wantsurl', $checkout);
        self::assertStringContainsString('$guestverificationstate?->is_locked() === true', $checkout);
        self::assertStringContainsString('commerce-checkout-login-gate', $template);
        self::assertStringContainsString('name="password"', $template);
    }


    public function test_personal_offer_signed_link_can_resolve_reserved_identity_without_generic_otp(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString('personal_offer_reserved_email', $checkout);
        self::assertStringContainsString("'identity_proof' => 'personal_offer_signed_link'", $checkout);
        self::assertStringContainsString('CommerceGuestCheckoutService::create()->identify(', $checkout);
        self::assertStringContainsString('$showguestidentity = false;', $checkout);
    }


    public function test_print_css_hides_theme_chrome_and_preserves_offer_badge(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );
        self::assertIsString($css);

        self::assertStringContainsString('.commerce-cart-print-item__badges > span:not(.commerce-personal-offer-badge)', $css);
        self::assertStringContainsString('.navbar-area', $css);
        self::assertStringContainsString('.sticky-header', $css);
        self::assertStringContainsString('body > header', $css);
    }

}
