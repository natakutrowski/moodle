<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797l731_payment_retry_reservation_contract_test extends \advanced_testcase {
    public function test_l731_contract_is_wired(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $coordinator = file_get_contents($root . 'classes/commerce/checkout/unified/CommerceCheckoutSeatReservationCoordinator.php');
        $router = file_get_contents($root . 'classes/payment/EventRouter.php');
        $service = file_get_contents($root . 'classes/commerce/education/reservation/CommercePedagogicalSeatReservationService.php');
        $checkout = file_get_contents($root . 'commerce_checkout.php');
        $template = file_get_contents($root . 'templates/checkout/page.mustache');

        self::assertStringContainsString('CHECKOUT_TTL = 10 * MINSECS', $coordinator);
        self::assertStringContainsString('PAYMENT_TTL = 10 * MINSECS', $coordinator);
        self::assertStringContainsString("\$event->type === 'checkout_expired'", $router);
        self::assertStringContainsString("['payment_failed', 'checkout_expired']", $router);
        self::assertStringContainsString('phasealreadystarted', $service);
        self::assertStringContainsString('CommerceCartSeatReservationPresenter', $checkout);
        self::assertStringContainsString("cart_seat_reservation', 'init'", $checkout);
        self::assertStringContainsString('data-cart-seat-countdown', $template);
    }
}
