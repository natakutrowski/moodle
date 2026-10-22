<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseResumePresenter;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;

/** C5.14.4 — an interrupted Buy Now remains visible and resumable. */
final class commerce_798c5144_direct_purchase_resume_navigation_test extends advanced_testcase {
    public function test_resume_presenter_exposes_the_existing_direct_purchase(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        CommerceDirectPurchaseSession::store(
            'EUR',
            'DONATION.ALF',
            70,
            1,
            ['donation_units' => 20],
            str_repeat('a', 32)
        );

        $view = CommerceDirectPurchaseResumePresenter::present();

        self::assertTrue($view['hasdirectpurchase']);
        self::assertStringContainsString(
            '/local/subscriptions/commerce_checkout.php',
            $view['directpurchaseresumeurl']
        );
        self::assertStringContainsString(
            'currency=EUR',
            $view['directpurchaseresumeurl']
        );
        self::assertStringContainsString(
            'flow=direct',
            $view['directpurchaseresumeurl']
        );
    }

    public function test_matching_paid_cart_can_clear_resume_without_touching_a_newer_buy_now(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        $currentuuid = str_repeat('b', 32);
        CommerceDirectPurchaseSession::store(
            'EUR',
            'COURSE_ACCESS.ARTICLES',
            68,
            1,
            [],
            $currentuuid
        );

        self::assertFalse(
            CommerceDirectPurchaseSession::clear_if_cart_uuid(
                str_repeat('c', 32)
            )
        );
        self::assertNotNull(
            CommerceDirectPurchaseSession::current_any_currency()
        );

        self::assertTrue(
            CommerceDirectPurchaseSession::clear_if_cart_uuid($currentuuid)
        );
        self::assertNull(
            CommerceDirectPurchaseSession::current_any_currency()
        );
    }

    public function test_cart_catalog_and_product_surface_render_the_shared_resume_partial(): void {
        global $CFG;

        $files = [
            '/local/subscriptions/templates/cart/page.mustache',
            '/local/subscriptions/templates/storefront/catalog.mustache',
            '/local/subscriptions/templates/storefront/product_commerce_panel.mustache',
        ];

        foreach ($files as $relative) {
            $source = (string)file_get_contents($CFG->dirroot . $relative);
            self::assertStringContainsString(
                '{{> local_subscriptions/checkout/direct_purchase_resume }}',
                $source
            );
        }

        $partial = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/direct_purchase_resume.mustache'
        );
        self::assertStringContainsString('{{#hasdirectpurchase}}', $partial);
        self::assertStringContainsString('data-direct-purchase-resume', $partial);
        self::assertStringContainsString(
            'href="{{directpurchaseresumeurl}}"',
            $partial
        );
    }

    public function test_paid_direct_order_uses_uuid_guard_before_clearing_resume(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            'CommercePurchaseFlow::is_direct($purchaseflow)',
            $source
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::clear_if_cart_uuid(',
            $source
        );
        self::assertStringContainsString(
            '$order->metadata[\'cart_uuid\']',
            $source
        );
    }
}
