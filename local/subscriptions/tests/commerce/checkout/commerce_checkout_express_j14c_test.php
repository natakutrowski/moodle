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

    public function test_buy_now_forms_use_direct_purchase_flow_with_safe_checkout_fallback(): void {
        global $CFG;

        $cartaction = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $panel = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache');

        self::assertStringContainsString("\$action === 'buynow'", $cartaction);
        self::assertStringContainsString('prepare_direct_product(', $cartaction);
        self::assertStringContainsString('CommerceDirectPurchaseSession::store(', $cartaction);
        self::assertStringContainsString('CommercePurchaseFlow::DIRECT', $cartaction);
        self::assertStringContainsString("'/local/subscriptions/commerce_checkout.php'", $cartaction);

        foreach ([$showroom, $storefront, $panel] as $template) {
            self::assertStringContainsString('name="action" value="buynow"', $template);
        }
    }

    public function test_order_result_restores_account_ready_confirmation(): void {
        global $CFG;
        $result = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');

        self::assertStringContainsString('commerce_guest_activation_ready_confirmation', $result);
        self::assertStringContainsString('if ($accountfinalised)', $result);
    }
}
