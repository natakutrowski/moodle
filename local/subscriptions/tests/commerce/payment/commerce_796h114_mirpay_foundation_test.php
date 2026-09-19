<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h114_mirpay_foundation_test extends \advanced_testcase {

    public function test_mirpay_is_known_and_rub_only(): void {
        self::assertTrue(\local_subscriptions\commerce\payment\method\CommercePaymentMethod::is_known('mir_pay'));
        self::assertContains('mir_pay', \local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue::keys());
        self::assertTrue(\local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility::supports('mir_pay', 'RUB', 'FR'));
        self::assertFalse(\local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility::supports('mir_pay', 'EUR', 'FR'));
    }


    public function test_mirpay_brand_asset_is_present_but_alfa_does_not_advertise_execution(): void {
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

        self::assertStringContainsString("CommercePaymentMethod::MIR_PAY => 'mirpay.svg'", $presenter);
        self::assertStringNotContainsString('CommercePaymentMethod::MIR_PAY', $provider);
    }

}
