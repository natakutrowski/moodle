<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\storefront\cart\CommerceStorefrontPromotionJoinCartContextResolver;

/** M8.2H1 regressions: own promotion-join hold stays resumable and modal buttons stay clickable. */
final class commerce_797m82h1_storefront_resume_and_modal_navigation_test extends advanced_testcase {
    public function test_direct_purchase_promotion_join_cart_is_resolved_as_own_hold(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        $uuid = str_repeat('a', 32);
        CommerceDirectPurchaseSession::store(
            'EUR',
            'DEV797-FR',
            63,
            1,
            ['operation' => 'promotion_join'],
            $uuid
        );

        self::assertSame(
            $uuid,
            CommerceStorefrontPromotionJoinCartContextResolver::create()
                ->excluded_cart_uuid(231, 'EUR', 'DEV797-FR')
        );
    }

    public function test_normal_cart_promotion_join_cart_is_resolved_as_own_hold(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        $customerid = 231;
        $currency = 'EUR';
        $uuid = str_repeat('b', 32);
        $cart = new CommerceCart(
            $uuid,
            $customerid,
            $currency,
            [
                new CommerceCartItem(
                    'DEV797-FR',
                    63,
                    1,
                    ['operation' => 'promotion_join']
                ),
            ]
        );

        $repository = new CommerceSessionCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $repository->save($keys->resolve($customerid, $currency), $cart);

        self::assertSame(
            $uuid,
            CommerceStorefrontPromotionJoinCartContextResolver::create()
                ->excluded_cart_uuid($customerid, $currency, 'DEV797-FR')
        );
    }

    public function test_non_promotion_join_line_is_never_excluded(): void {
        $this->resetAfterTest(true);
        CommerceDirectPurchaseSession::clear();

        $customerid = 231;
        $currency = 'EUR';
        $cart = new CommerceCart(
            str_repeat('c', 32),
            $customerid,
            $currency,
            [new CommerceCartItem('DEV797-FR', 63, 1, [])]
        );

        $repository = new CommerceSessionCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $repository->save($keys->resolve($customerid, $currency), $cart);

        self::assertNull(
            CommerceStorefrontPromotionJoinCartContextResolver::create()
                ->excluded_cart_uuid($customerid, $currency, 'DEV797-FR')
        );
    }

    public function test_storefront_projection_passes_own_cart_to_join_eligibility(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/repository/'
            . 'CommerceStorefrontRepository.php'
        );

        self::assertStringContainsString(
            'CommerceStorefrontPromotionJoinCartContextResolver::create()',
            $source
        );
        self::assertStringContainsString(
            '$promotionjoinexcludedcartuuid',
            $source
        );
        self::assertMatchesRegularExpression(
            '/->resolve\(\s*\(int\)\$USER->id,\s*\$summary->get_sku\(\),\s*time\(\),\s*\$promotionjoinexcludedcartuuid\s*\)/s',
            $source
        );
    }

    public function test_added_modal_keeps_view_cart_above_backdrop(): void {
        global $CFG;

        $template = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/cart_added_modal.mustache'
        );
        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            'href="{{carturl}}" class="btn btn-primary flex-grow-1"',
            $template
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-cart-feedback__backdrop\s*\{[^}]*z-index:\s*0;/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-cart-feedback__dialog\s*\{[^}]*z-index:\s*1;/s',
            $css
        );
    }
}
