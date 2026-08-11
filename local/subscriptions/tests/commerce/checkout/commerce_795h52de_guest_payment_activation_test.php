<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivator;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_795h52de_guest_payment_activation_test extends \advanced_testcase {
    public function test_guest_session_can_be_attached_to_native_payment(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR');
        $session = $service->identify($session, 'guest-h52de@example.com', 'Guest', 'Customer');

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->attach_payment($session, 'purchase-h52de', 'payment-h52de');

        $this->assertSame('payment_pending', $session->get_status());
        $this->assertSame('purchase-h52de', $session->get_purchase_reference());
        $this->assertSame('payment-h52de', $session->get_payment_reference());
    }

    public function test_successful_payment_activates_provisional_account(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR');
        $session = $service->identify($session, 'activate-h52de@example.com', 'Active', 'Customer');
        $userid = $session->get_user_id();

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->attach_payment($session, 'purchase-activate-h52de', 'payment-activate-h52de');
        $session = (new CommerceGuestAccountActivator($DB, $repository))
            ->activate_for_purchase('purchase-activate-h52de');

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame('active', $session?->get_status());
        $this->assertSame(0, (int) $user->suspended);
        $this->assertSame(1, (int) $user->confirmed);
        $this->assertSame(1, (int) get_user_preferences('auth_forcepasswordchange', 0, $userid));
    }

    public function test_public_checkout_no_longer_requires_login(): void {
        $root = dirname(__DIR__, 3);
        $checkout = file_get_contents($root . '/commerce_checkout.php');
        $action = file_get_contents($root . '/commerce_checkout_action.php');

        $this->assertStringNotContainsString('require_login();', $checkout);
        $this->assertStringNotContainsString('require_login();', $action);
        $this->assertStringContainsString('CommerceCheckoutIdentityResolver', $checkout);
        $this->assertStringContainsString('attach_payment', $action);
    }
}
