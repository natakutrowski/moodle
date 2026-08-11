<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_customer_j12f_test extends advanced_testcase {
    public function test_cart_and_checkout_use_typed_placeholders(): void {
        $cart = file_get_contents(
            dirname(__DIR__, 3) . '/templates/cart/page.mustache'
        );
        $checkout = file_get_contents(
            dirname(__DIR__, 3) . '/templates/checkout/page.mustache'
        );
        $presenter = file_get_contents(
            dirname(__DIR__, 3)
                . '/classes/commerce/cart/presentation/CommerceCartPresenter.php'
        );

        self::assertIsString($cart);
        self::assertIsString($checkout);
        self::assertIsString($presenter);
        self::assertStringContainsString('{{placeholdericon}}', $cart);
        self::assertStringContainsString('{{placeholdericon}}', $checkout);
        self::assertStringNotContainsString('>CampusFR</div>', $cart);
        self::assertStringNotContainsString('>CampusFR</div>', $checkout);
        self::assertStringContainsString(
            'CommerceProductVisualAuditService::placeholder_icon',
            $presenter
        );
    }

    public function test_order_result_has_product_visuals_and_simplified_actions(): void {
        $controller = file_get_contents(
            dirname(__DIR__, 3) . '/order_result.php'
        );
        $css = file_get_contents(
            dirname(__DIR__, 3) . '/styles/order_result.css'
        );

        self::assertIsString($controller);
        self::assertIsString($css);
        self::assertStringContainsString(
            "get_cover_url('checkout')",
            $controller
        );
        self::assertStringContainsString(
            'commerce-order-item__visual',
            $controller
        );
        self::assertStringContainsString('fa-solid fa-receipt', $controller);
        self::assertStringContainsString('fa-solid fa-headset', $controller);
        self::assertStringNotContainsString(
            "get_string('commerce_i2_my_orders', 'local_subscriptions'), ['class' => 'btn",
            $controller
        );
        self::assertStringContainsString(
            '.commerce-order-next__primary a',
            $css
        );
    }
}
