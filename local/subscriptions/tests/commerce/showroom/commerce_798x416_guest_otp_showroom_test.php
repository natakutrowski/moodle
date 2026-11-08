<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as Gate;

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');

/** P2O: anonymous DEV showroom must lead to existing guest checkout + OTP, no frontend warning. */
final class commerce_798x416_guest_otp_showroom_test extends advanced_testcase {
    public function test_canonical_guest_pilot_stays_exactly_dev_and_sandbox(): void {
        $args = [8, 'published', Gate::KEY, Gate::TEMPLATE,
            Gate::DEV_ROOT, Gate::DESIGN, Gate::DESIGN_VERSION, Gate::PUBLIC_PILOT_SLUGS];
        self::assertTrue(Gate::eligible_published_pilot(...$args));
        $args[4] = 'https://campusfr.fr';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
        $args[4] = Gate::DEV_ROOT;
        $args[1] = 'draft';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
    }

    public function test_guest_route_keeps_gate_but_has_no_banner_or_forced_login(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/showroom.php');
        self::assertStringContainsString('subscription_config::guard_public_access();', $page);
        self::assertMatchesRegularExpression('/CommerceShowroomDevCheckoutTestGate\s*::\s*eligible_published_pilot\s*\(/', $page);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $page);
        self::assertStringContainsString('noindex,nofollow,noarchive', $page);
        self::assertStringContainsString('Cache-Control: private, no-store', $page);
        self::assertStringNotContainsString('require_login();', $page);
        self::assertStringContainsString('if ($checkouttest) {', $page);
        self::assertStringNotContainsString('if ($checkouttest || $isdevpublishedpilot)', $page);
        self::assertStringNotContainsString('DEV · Boutique CampusFR de test', $page);
        self::assertStringNotContainsString('dev_checkout_shadow_post.js', $page);
    }

    public function test_otp_and_guest_cart_remain_owned_by_commerce(): void {
        $root = dirname(__DIR__, 3);
        $cart = (string)file_get_contents($root . '/cart_action.php');
        $checkout = (string)file_get_contents($root . '/commerce_checkout.php');
        $otp = (string)file_get_contents($root . '/ajax/guest_identity_otp_start.php');
        self::assertStringContainsString('require_sesskey();', $cart);
        self::assertStringContainsString('CommerceGuestCartCustomerResolver', $cart);
        self::assertStringContainsString('CommerceGuestCheckoutService', $checkout);
        self::assertStringContainsString('CommerceGuestIdentityVerificationService', $checkout);
        self::assertStringContainsString('CommerceGuestIdentityOtpChallengeService', $otp);
    }
}
