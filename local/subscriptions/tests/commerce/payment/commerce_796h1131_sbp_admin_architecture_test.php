<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1131_sbp_admin_architecture_test extends \advanced_testcase {

    public function test_sbp_assets_and_checkout_presentation_are_wired(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/presentation/CommerceCheckoutPaymentMethodPresenter.php'
        );
        self::assertIsString($presenter);
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
        global $CFG;

        $architecture = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        );
        self::assertIsString($architecture);

        self::assertStringContainsString("CommercePaymentMethod::SBP => 'sbp.svg'", $presenter);
        self::assertStringContainsString('sbp_logo.png', $checkout);
        self::assertStringContainsString('{{#hassbpcandidate}}', $template);
        self::assertStringContainsString('data-checkout-alfa-sbp', $template);
        self::assertMatchesRegularExpression("/'sbp'\\s*=>\\s*\\[\\s*'file'\\s*=>\\s*'sbp\\.svg'/s", $architecture);
    }


    public function test_alfa_provider_exposes_sbp_only_when_gateway_is_configured_for_it(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        self::assertIsString($provider);

        self::assertStringContainsString('CommercePaymentMethod::SBP', $provider);
        self::assertStringContainsString('is_sbp_configured()', $provider);
    }

}
