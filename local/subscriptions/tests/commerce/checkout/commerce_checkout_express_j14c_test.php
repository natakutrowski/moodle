<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\express\CommerceCheckoutExpressService;

final class commerce_checkout_express_j14c_test extends \advanced_testcase {
    public function test_current_legal_acceptance_is_persisted_for_authenticated_customer(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $service = new CommerceCheckoutExpressService();
        self::assertFalse($service->has_current_legal_acceptance((int)$user->id));

        $service->record_legal_acceptance((int)$user->id);
        self::assertTrue($service->has_current_legal_acceptance((int)$user->id));
    }

    public function test_buy_now_forms_request_express_checkout_with_safe_fallback(): void {
        global $CFG;

        $cartaction = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $panel = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache');

        self::assertStringContainsString("optional_param('express', 0, PARAM_BOOL)", $cartaction);
        self::assertStringContainsString('CommerceCheckoutExpressService', $cartaction);
        self::assertStringContainsString('name="express" value="1"', $showroom);
        self::assertStringContainsString('name="express" value="1"', $storefront);
        self::assertStringContainsString('name="express" value="1"', $panel);
        self::assertStringContainsString("'checkout_mode' => 'express'", file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/express/CommerceCheckoutExpressService.php'
        ));
    }

    public function test_order_result_restores_account_ready_confirmation(): void {
        global $CFG;
        $result = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');

        self::assertStringContainsString('commerce_guest_activation_ready_confirmation', $result);
        self::assertStringContainsString('if ($accountfinalised)', $result);
    }
}
