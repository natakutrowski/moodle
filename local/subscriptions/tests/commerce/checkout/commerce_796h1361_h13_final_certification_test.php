<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseOrigin;
use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\policy\CommercePaymentMarketSignalPolicy;
use local_subscriptions\commerce\payment\policy\CommercePaymentRecommendationProfileRegistry;

/**
 * H13.6.1 — final H13 closure certification.
 *
 * Manual E2E coverage already validated:
 * - EUR / USD / GBP / RUB;
 * - cart and isolated Buy Now;
 * - storefront / product / showroom / personal offer;
 * - guest and authenticated checkout;
 * - mobile purchase journey;
 * - Alfa / SBP and global Stripe/PayPal surfaces.
 *
 * This test freezes the architectural and static contracts that H13 introduced.
 */
final class commerce_796h1361_h13_final_certification_test extends \advanced_testcase {
    public function test_currency_selection_contract_remains_multi_currency(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/selection/'
            . 'CommerceCurrencySelectionService.php'
        );

        self::assertStringNotContainsString(
            "? 'RUB' : 'EUR'",
            $source
        );
        self::assertStringContainsString(
            'CommerceCurrencySelectionContext',
            $source
        );
    }

    public function test_representative_payment_profiles_cover_eur_usd_gbp_rub(): void {
        $registry = new CommercePaymentRecommendationProfileRegistry();

        foreach (['EUR', 'USD', 'GBP', 'RUB'] as $currency) {
            self::assertNotEmpty(
                $registry->order($currency, 'ZZ'),
                $currency
            );
        }

        self::assertSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('USD', 'US'), 0, 6)
        );
        self::assertSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('GBP', 'GB'), 0, 6)
        );
        self::assertNotSame(
            array_slice($registry->order('EUR', 'FR'), 0, 6),
            array_slice($registry->order('RUB', 'RU'), 0, 6)
        );
    }

    public function test_klarna_usd_guard_remains_active(): void {
        self::assertFalse(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'USD',
                'US'
            )
        );

        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'EUR',
                'FR'
            )
        );

        self::assertTrue(
            CommercePaymentMethodMarketEligibility::supports(
                CommercePaymentMethod::KLARNA,
                'GBP',
                'GB'
            )
        );
    }

    public function test_rub_native_methods_remain_vpn_resilient_and_currency_bounded(): void {
        foreach ([
            CommercePaymentMethod::ALFA_PAY,
            CommercePaymentMethod::SBP,
            CommercePaymentMethod::SBERPAY,
            CommercePaymentMethod::MIR_PAY,
        ] as $method) {
            self::assertTrue(
                CommercePaymentMethodMarketEligibility::supports(
                    $method,
                    'RUB',
                    'FR'
                ),
                $method
            );

            self::assertFalse(
                CommercePaymentMethodMarketEligibility::supports(
                    $method,
                    'EUR',
                    'RU'
                ),
                $method
            );
        }

        $policy = new CommercePaymentMarketSignalPolicy();

        self::assertFalse(
            $policy->country_is_authoritative_for(
                CommercePaymentMethod::SBP,
                'RUB'
            )
        );
        self::assertTrue(
            $policy->country_is_authoritative_for(
                CommercePaymentMethod::KLARNA,
                'EUR'
            )
        );
    }

    public function test_buy_now_is_isolated_from_normal_cart(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );
        $runtime = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutRuntime.php'
        );

        $start = strpos(
            $action,
            "if (\$action === 'buynow')"
        );
        $end = strpos(
            $action,
            "} else if (\$action === 'remove')",
            $start
        );

        self::assertNotFalse($start);
        self::assertNotFalse($end);

        $block = substr(
            $action,
            $start,
            $end - $start
        );

        self::assertStringContainsString(
            'prepare_direct_product(',
            $block
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::store(',
            $block
        );
        self::assertStringNotContainsString(
            'clear_cart(',
            $block
        );

        self::assertStringContainsString(
            '$this->cart->direct_snapshot(',
            $runtime
        );
    }

    public function test_buy_now_surfaces_have_no_legacy_provider_experience(): void {
        global $CFG;

        foreach ([
            'templates/storefront/product_card.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/showroom/offer.mustache',
            'templates/checkout/page.mustache',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/'
                . $relative
            );

            self::assertStringNotContainsString(
                'data-provider-experience',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'checkout/provider_experience',
                $source,
                $relative
            );
        }

        self::assertFileDoesNotExist(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/provider_experience.mustache'
        );
        self::assertFileDoesNotExist(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/provider_experience.js'
        );
    }

    public function test_direct_purchase_origin_contract_is_preserved(): void {
        self::assertSame(
            'commerce_checkout_back_storefront',
            CommercePurchaseOrigin::checkout_back_string(
                CommercePurchaseFlow::DIRECT,
                CommercePurchaseOrigin::STOREFRONT
            )
        );
        self::assertSame(
            'commerce_checkout_back_product',
            CommercePurchaseOrigin::checkout_back_string(
                CommercePurchaseFlow::DIRECT,
                CommercePurchaseOrigin::PRODUCT
            )
        );
        self::assertSame(
            'commerce_checkout_back_showroom',
            CommercePurchaseOrigin::checkout_back_string(
                CommercePurchaseFlow::DIRECT,
                CommercePurchaseOrigin::SHOWROOM
            )
        );
        self::assertSame(
            'commerce_checkout_back_offer',
            CommercePurchaseOrigin::checkout_back_string(
                CommercePurchaseFlow::DIRECT,
                CommercePurchaseOrigin::PERSONAL_OFFER
            )
        );
    }

    public function test_cart_and_checkout_share_same_reassurance_contract(): void {
        global $CFG;

        $cart = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/commerce/payment_reassurance.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        foreach ([
            '{{checkoutsecureencrypted}}',
            '{{checkoutdataprotected}}',
            '{{instantaccesslabel}}',
        ] as $token) {
            self::assertStringContainsString(
                $token,
                $cart
            );
            self::assertStringContainsString(
                $token,
                $checkout
            );
        }

        self::assertStringNotContainsString(
            'stripeiconurl',
            $cart
        );
        self::assertStringNotContainsString(
            'alfaiconurl',
            $cart
        );
    }

    public function test_checkout_has_no_legacy_global_provider_instruction(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $runtime = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringNotContainsString(
            '{{subtitle}}',
            $template
        );
        self::assertStringNotContainsString(
            '{{paymentdescription}}',
            $template
        );
        self::assertStringNotContainsString(
            "'paymentdescription' =>",
            $runtime
        );
    }

    public function test_mobile_money_values_are_kept_atomic(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            'white-space: nowrap',
            $css
        );
        self::assertStringContainsString(
            '.commerce-cart-summary__totals dd',
            $css
        );
    }

    public function test_checkout_keeps_current_payment_surfaces_only(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $alfa = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'data-checkout-payment-splash',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-payment-form',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-alfa-iframe',
            $alfa
        );
    }
}
