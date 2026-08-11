<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_guest_checkout_inline_login_test extends advanced_testcase {
    public function test_existing_account_login_is_rendered_inside_checkout(): void {
        $root = dirname(__DIR__, 3);
        $checkout = (string)file_get_contents($root . '/commerce_checkout.php');
        $template = (string)file_get_contents($root . '/templates/checkout/page.mustache');

        $this->assertStringContainsString('get_login_token()', $checkout);
        $this->assertStringContainsString('$SESSION->wantsurl', $checkout);
        $this->assertStringContainsString('hasembeddedlogin', $checkout);
        $this->assertStringContainsString('commerce-checkout-login-gate', $template);
        $this->assertStringContainsString('name="logintoken"', $template);
        $this->assertStringContainsString('name="username"', $template);
        $this->assertStringContainsString('name="password"', $template);
        $this->assertStringContainsString('{{^existingaccount}}', $template);
    }

    public function test_personal_offer_identity_is_resolved_before_payment(): void {
        $root = dirname(__DIR__, 3);
        $checkout = (string)file_get_contents($root . '/commerce_checkout.php');

        $this->assertStringContainsString(
            'Personal Offer identity is already authoritative',
            $checkout
        );
        $this->assertStringContainsString(
            'CommerceGuestCheckoutService::create()->identify',
            $checkout
        );
    }

    public function test_print_css_hides_theme_chrome_and_does_not_restyle_nested_offer_badge(): void {
        $root = dirname(__DIR__, 3);
        $css = (string)file_get_contents($root . '/styles/storefront.css');

        $this->assertStringContainsString(
            '.commerce-cart-print-item__badges > span:not(.commerce-personal-offer-badge)',
            $css
        );
        $this->assertStringContainsString('.navbar-area', $css);
        $this->assertStringContainsString('.sticky-header', $css);
        $this->assertStringContainsString('body > header', $css);
    }
}
