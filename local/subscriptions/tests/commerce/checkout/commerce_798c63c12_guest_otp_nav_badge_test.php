<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseResumePresenter;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;

/** C6.3C.1/.2 — OTP failure UX and Direct Purchase navbar indicator. */
final class commerce_798c63c12_guest_otp_nav_badge_test extends advanced_testcase {
    public function test_otp_delivery_failure_has_dedicated_retryable_customer_message(): void {
        global $CFG;

        $checkout = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            "'guestotpdeliveryfailed' => get_string('commerce_guest_identity_otp_delivery_failed'",
            $checkout
        );
        self::assertStringContainsString(
            'data-otp-delivery-failed="{{guestotpdeliveryfailed}}"',
            $template
        );
        self::assertStringContainsString("payload.code === 'delivery_failed'", $amd);
        self::assertStringContainsString("copy('otpDeliveryFailed')", $amd);
        self::assertStringContainsString("lastIssuedSignature = '';", $amd);
        self::assertStringContainsString('setResendCountdown(0);', $amd);
        self::assertStringContainsString('otpCode.disabled = true;', $amd);
    }

    public function test_nav_badge_is_registered_only_from_shared_direct_purchase_presenter(): void {
        global $CFG;

        $hook = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/output/hook_callbacks.php'
        );
        $amd = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/direct_purchase_nav_badge.js'
        );

        self::assertStringContainsString(
            'CommerceDirectPurchaseResumePresenter::present()',
            $hook
        );
        self::assertStringContainsString("if (!\$resume['hasdirectpurchase'])", $hook);
        self::assertStringContainsString(
            "'local_subscriptions/direct_purchase_nav_badge'",
            $hook
        );
        self::assertStringContainsString('/local/subscriptions/cart.php', $amd);
        self::assertStringContainsString('data-direct-purchase-nav-badge', $amd);
        self::assertStringContainsString('NAVIGATION_SELECTOR', $amd);
        self::assertStringContainsString("'.navbar-area'", $amd);
        // C6.3C.4: the public Boutique and Cart are BOTH legitimate targets.
        // Generic shopping-cart icons in CRM Commerce, Sales and content are not.
        self::assertStringContainsString('EXPLICIT_CART_SELECTOR', $amd);
        self::assertStringContainsString('isPurchaseNavigationRoute(link)', $amd);
        self::assertStringContainsString("'/boutique'", $amd);
        self::assertStringContainsString("'/shop'", $amd);
        self::assertStringContainsString("'/magazin'", $amd);
        self::assertStringContainsString("'/panier'", $amd);
        self::assertStringContainsString("'/cart'", $amd);
        self::assertStringContainsString("'/korzina'", $amd);
        self::assertStringContainsString('/local/subscriptions/digital_catalog.php', $amd);
        self::assertStringContainsString('url.origin !== window.location.origin', $amd);
        self::assertStringContainsString("'a[data-region=\"cart\"]'", $amd);
        self::assertStringContainsString("'button[data-action=\"cart\"]'", $amd);
        self::assertStringNotContainsString('CART_ICON_SELECTOR', $amd);
        self::assertStringNotContainsString("'.fa-shopping-cart'", $amd);
        self::assertStringNotContainsString("'.fa-cart-shopping'", $amd);
        self::assertStringContainsString('new MutationObserver(schedule)', $amd);
        self::assertStringContainsString('OBSERVE_FOR_MS = 15000', $amd);
        self::assertStringContainsString("background: '#f72585'", $amd);
        self::assertStringContainsString("badge.textContent = '';", $amd);
        self::assertStringContainsString("bottom: '1px'", $amd);
        self::assertStringContainsString("width: '10px'", $amd);
        self::assertStringNotContainsString("badge.textContent = String(config.count || 1)", $amd);
        self::assertStringNotContainsString("'count' => 1", $hook);

        // Moodle production normally executes the AMD build, not amd/src.
        // Keep the same safety fix in the shipped browser bundle.
        $built = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/build/direct_purchase_nav_badge.min.js'
        );
        // Grunt/Uglify rewrites function expressions and normalises quote styles.
        // Assert the shipped behaviour rather than an unminified helper name.
        // The source assertions above guard route filtering and admin exclusions.
        foreach ([
            '/local/subscriptions/cart.php',
            '/local/subscriptions/digital_catalog.php',
            '/boutique', '/shop', '/magazin',
            '/panier', '/cart', '/korzina',
        ] as $publicroute) {
            self::assertStringContainsString($publicroute, $built);
        }
        self::assertStringContainsString('data-direct-purchase-nav-badge', $built);
        self::assertStringNotContainsString('CART_ICON_SELECTOR', $built);
        self::assertStringNotContainsString('fa-shopping-cart', $built);
        self::assertStringNotContainsString('fa-cart-shopping', $built);
    }

    public function test_presenter_still_exposes_one_durable_buy_now_for_the_badge(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        self::assertFalse(CommerceDirectPurchaseResumePresenter::present()['hasdirectpurchase']);

        CommerceDirectPurchaseSession::store(
            'EUR',
            'COURSE_ACCESS.ARTICLES',
            68,
            1,
            [],
            str_repeat('d', 32)
        );

        $resume = CommerceDirectPurchaseResumePresenter::present();
        self::assertTrue($resume['hasdirectpurchase']);
        self::assertNotSame('', $resume['directpurchaseresumetitle']);
        self::assertStringContainsString('/local/subscriptions/commerce_checkout.php', $resume['directpurchaseresumeurl']);
    }
}
