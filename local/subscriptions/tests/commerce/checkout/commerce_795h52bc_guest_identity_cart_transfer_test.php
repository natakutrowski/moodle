<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService;

final class commerce_795h52bc_guest_identity_cart_transfer_test extends \advanced_testcase {
    public function test_guest_cart_is_transferred_and_removed(): void {
        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $guest = new CommerceCart(str_repeat('a', 32), 0, 'EUR', [
            new CommerceCartItem('COURSE-A1', 42, 1),
        ]);
        $repository->save($keys->resolve(0, 'EUR'), $guest);

        $target = (new CommerceGuestCartTransferService($repository, $keys))->transfer(123, 'EUR');

        $this->assertSame(123, $target->get_customer_id());
        $this->assertCount(1, $target->get_items());
        $this->assertNull($repository->find($keys->resolve(0, 'EUR')));
        $this->assertNotNull($repository->find($keys->resolve(123, 'EUR')));
    }

    public function test_existing_cart_is_merged_without_duplicate_line(): void {
        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $repository->save($keys->resolve(0, 'EUR'), new CommerceCart(str_repeat('b', 32), 0, 'EUR', [
            new CommerceCartItem('DIGITAL-1', 7, 1),
        ]));
        $repository->save($keys->resolve(321, 'EUR'), new CommerceCart(str_repeat('c', 32), 321, 'EUR', [
            new CommerceCartItem('DIGITAL-1', 7, 1),
            new CommerceCartItem('COURSE-A2', 8, 1),
        ]));

        $target = (new CommerceGuestCartTransferService($repository, $keys))->transfer(321, 'EUR');

        $this->assertCount(2, $target->get_items());
        $this->assertSame(str_repeat('c', 32), $target->get_uuid());
    }

    public function test_durable_guest_cart_restores_after_login_session_regeneration(): void {
        $repository = new CommerceInMemoryCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $durable = (new CommerceCart(str_repeat('d', 32), 0, 'EUR', [
            new CommerceCartItem('COURSE-A1', 42, 1),
        ]))->to_array();

        // The anonymous session cart is deliberately absent, as after Moodle login regeneration.
        $target = (new CommerceGuestCartTransferService($repository, $keys))->transfer(456, 'EUR', $durable);

        $this->assertNotNull($target);
        $this->assertSame(456, $target->get_customer_id());
        $this->assertCount(1, $target->get_items());
        $this->assertNotNull($repository->find($keys->resolve(456, 'EUR')));
    }

    public function test_guest_checkout_captures_cart_before_identity_resolution(): void {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents(
            $root . '/classes/commerce/checkout/guest/CommerceGuestCheckoutService.php'
        );
        $resume = file_get_contents($root . '/guest_checkout_resume.php');
        $checkout = file_get_contents($root . '/commerce_checkout.php');
        $action = file_get_contents($root . '/commerce_checkout_action.php');

        $this->assertStringContainsString('\'guest_cart_snapshot\' => $durablecart', $service);
        $this->assertStringContainsString("['guest_cart_snapshot']", $resume);
        $this->assertStringContainsString('CommerceGuestCartRecoveryService::create()->recover_current', $checkout);
        $this->assertStringContainsString('CommerceGuestCartRecoveryService::create()->recover_current', $action);
    }

    public function test_public_guest_entrypoint_is_exposed_from_cart(): void {
        $root = dirname(__DIR__, 3);
        $cart = file_get_contents($root . '/cart.php');
        $page = file_get_contents($root . '/commerce_checkout.php');
        $provisioner = file_get_contents($root . '/classes/commerce/checkout/guest/CommerceGuestAccountProvisioner.php');

        $this->assertStringContainsString("'/local/subscriptions/commerce_checkout.php'", $cart);
        $this->assertStringContainsString('CommerceGuestCheckoutService::create()', $page);
        $this->assertStringContainsString("'Aa#' . bin2hex(random_bytes(24))", $provisioner);
    }
}
