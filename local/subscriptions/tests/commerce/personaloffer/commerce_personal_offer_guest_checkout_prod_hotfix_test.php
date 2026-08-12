<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_guest_checkout_prod_hotfix_test extends \advanced_testcase {
    public function test_checkout_action_resolves_provisional_customer_before_personal_offer_lookup(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        $sessionpos = strpos($source, '$guestsession = $token !==');
        $offerpos = strpos($source, '$cartoffer = $personaloffers->get_cart_offer($offercustomerid, $currency)');

        $this->assertNotFalse($sessionpos);
        $this->assertNotFalse($offerpos);
        $this->assertLessThan($offerpos, $sessionpos);

        $this->assertStringContainsString(
            "in_array(\$guestsession->get_status(), ['provisional', 'payment_pending'], true)",
            $source
        );
        $this->assertStringContainsString(
            '$offercustomerid = $guestsession->get_user_id();',
            $source
        );
        $this->assertStringNotContainsString(
            '$cartoffer = $personaloffers->get_cart_offer(0, $currency);',
            $source
        );
    }

    public function test_currency_switch_keeps_same_provisional_account(): void {
        global $CFG;

        $page = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $service = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCheckoutService.php'
        );

        $this->assertStringContainsString(
            'switch_provisional_currency(',
            $page
        );
        $this->assertStringContainsString(
            'public function switch_provisional_currency(',
            $service
        );
        $this->assertStringContainsString(
            '$this->carts->transfer($session->get_user_id(), $currency)',
            $service
        );
        $this->assertStringContainsString(
            "'currency' => \$currency",
            $service
        );
        $this->assertStringContainsString(
            "'purchasereference' => null",
            $service
        );
        $this->assertStringContainsString(
            "'paymentreference' => null",
            $service
        );
    }
}
