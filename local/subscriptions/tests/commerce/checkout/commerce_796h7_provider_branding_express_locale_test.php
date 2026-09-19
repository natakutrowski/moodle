<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h7_provider_branding_express_locale_test extends \advanced_testcase {

    public function test_provider_brand_assets_are_exposed(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        foreach (['paypallogourl', 'linklogourl', 'klarnalogourl', 'alfapaylogourl', 'sbplogourl'] as $key) {
            self::assertStringContainsString("'" . $key . "' =>", $checkout);
        }
    }


    public function test_express_checkout_lifecycle_is_current_and_locale_aware(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        self::assertIsString($js);
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString("'expressCheckout'", $js);
        self::assertStringContainsString("'availablepaymentmethodschange'", $js);
        self::assertStringContainsString("'confirm'", $js);
        self::assertStringContainsString("'locale' => current_language()", $checkout);
    }

}
