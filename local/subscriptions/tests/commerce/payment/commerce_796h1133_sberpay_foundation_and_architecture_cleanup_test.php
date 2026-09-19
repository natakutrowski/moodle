<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1133_sberpay_foundation_and_architecture_cleanup_test extends \advanced_testcase {

    public function test_sberpay_is_known_and_rub_only(): void {
        self::assertTrue(\local_subscriptions\commerce\payment\method\CommercePaymentMethod::is_known('sberpay'));
        self::assertContains('sberpay', \local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue::keys());
        self::assertTrue(\local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility::supports('sberpay', 'RUB', 'FR'));
        self::assertFalse(\local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility::supports('sberpay', 'EUR', 'FR'));
    }


    public function test_sberpay_brand_asset_is_present_but_alfa_does_not_advertise_execution(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/presentation/CommerceCheckoutPaymentMethodPresenter.php'
        );
        self::assertIsString($presenter);
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        self::assertIsString($provider);

        self::assertStringContainsString("CommercePaymentMethod::SBERPAY => 'sberpay.svg'", $presenter);
        self::assertStringNotContainsString('CommercePaymentMethod::SBERPAY', $provider);
    }

}
