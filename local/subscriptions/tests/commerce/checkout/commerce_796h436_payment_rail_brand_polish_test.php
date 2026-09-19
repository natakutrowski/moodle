<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h436_payment_rail_brand_polish_test extends \advanced_testcase {

    public function test_card_and_paypal_brand_assets_are_explicit(): void {
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

        self::assertStringContainsString("'visaiconurl' =>", $checkout);
        self::assertStringContainsString("'mastercardiconurl' =>", $checkout);
        self::assertStringContainsString("'paypallogourl' =>", $checkout);
        self::assertStringContainsString('{{visaiconurl}}', $template);
        self::assertStringContainsString('{{mastercardiconurl}}', $template);
        self::assertStringContainsString('{{paypallogourl}}', $template);
    }

}
