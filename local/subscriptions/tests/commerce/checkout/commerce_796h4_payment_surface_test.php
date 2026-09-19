<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h4_payment_surface_test extends \advanced_testcase {

    public function test_express_methods_are_split_from_standard_radio_choices(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('$expresspaymentmethods', $checkout);
        self::assertStringContainsString('!in_array(', $checkout);
        self::assertStringContainsString('data-checkout-express-wallet-element', $template);
        self::assertStringContainsString('name="paymentmethod"', $template);
    }

}
