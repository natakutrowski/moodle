<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseOrigin;

final class commerce_796h1334_purchase_origin_return_contract_test extends \advanced_testcase {
    public function test_direct_checkout_back_labels_are_origin_specific(): void {
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

    public function test_cart_checkout_always_returns_to_cart(): void {
        self::assertSame(
            'commerce_checkout_back_cart',
            CommercePurchaseOrigin::checkout_back_string(
                CommercePurchaseFlow::CART,
                CommercePurchaseOrigin::SHOWROOM
            )
        );
    }

    public function test_buy_now_surfaces_emit_explicit_origin(): void {
        global $CFG;

        $catalog = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_card.mustache'
        );
        $product = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );
        $showroom = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/showroom/offer.mustache'
        );

        self::assertStringContainsString(
            'name="source" value="storefront"',
            $catalog
        );
        self::assertStringContainsString(
            'name="source" value="product"',
            $product
        );
        self::assertStringContainsString(
            'name="source" value="showroom"',
            $showroom
        );
    }

    public function test_checkout_launch_persists_origin_return(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        $context = strpos(
            $source,
            '$context = new CommerceCheckoutContext('
        );
        self::assertNotFalse($context);

        $block = substr($source, $context, 3800);

        self::assertStringContainsString(
            "'checkout_source' => \$source",
            $block
        );
        self::assertStringContainsString(
            "'origin_return' => \$originreturn",
            $block
        );
    }

    public function test_cancelled_direct_order_result_prefers_origin_over_normal_cart(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            '$directoriginurl',
            $source
        );
        self::assertStringContainsString(
            "CommercePurchaseOrigin::result_back_string(",
            $source
        );
        self::assertStringContainsString(
            'if ($directoriginurl === null)',
            $source
        );
    }
}
