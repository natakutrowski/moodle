<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_checkout_currency_m3h9_test extends \advanced_testcase {
    public function test_checkout_currency_switch_preserves_direct_checkout_destination(): void {
        global $CFG;

        $endpoint = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/offer_currency.php'
        );
        $checkout = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "'destination' => 'checkout'",
            $endpoint
        );
        $this->assertStringContainsString(
            "/local/subscriptions/offer_currency.php",
            $checkout
        );
    }

    public function test_currency_switch_never_transports_a_price_or_showroom_override(): void {
        global $CFG;

        $endpoint = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/offer_currency.php'
        );

        $this->assertStringNotContainsString("'price' =>", $endpoint);
        $this->assertStringNotContainsString("'showroom' =>", $endpoint);
        $this->assertStringContainsString("'token' => \$token", $endpoint);
        $this->assertStringContainsString("'currency' => \$currency", $endpoint);
    }
}
