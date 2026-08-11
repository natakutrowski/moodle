<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\lifecycle\CommerceCartLifecycleService;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;

final class commerce_cart_lifecycle_test extends \advanced_testcase {
    public function test_only_the_cart_frozen_into_the_paid_purchase_is_deleted(): void {
        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $service = new CommerceCartLifecycleService($repository, $keys);
        $key = $keys->resolve(42, 'EUR');
        $cart = new CommerceCart(str_repeat('a', 32), 42, 'EUR');
        $repository->save($key, $cart);

        $this->assertFalse($service->clear_converted_cart(42, 'EUR', str_repeat('b', 32)));
        $this->assertNotNull($repository->find($key));

        $this->assertTrue($service->clear_converted_cart(42, 'EUR', str_repeat('a', 32)));
        $this->assertNull($repository->find($key));
        $this->assertFalse($service->clear_converted_cart(42, 'EUR', str_repeat('a', 32)));
    }

    public function test_checkout_freezes_cart_identity_and_cart_page_exposes_clear_action(): void {
        global $CFG;

        $builder = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchaseBuilder.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache'
        );
        $resultpage = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        $this->assertStringContainsString("'cart_uuid'", $builder);
        $this->assertStringContainsString("'cart_customerid'", $builder);
        $this->assertStringContainsString("'cart_currency'", $builder);
        $this->assertStringContainsString('name="action" value="clear"', $template);
        $this->assertStringContainsString('CommerceCartLifecycleService::create()', $resultpage);
        $this->assertStringContainsString("unset(\$metadata['guest_cart_snapshot'])", $resultpage);
    }
}
