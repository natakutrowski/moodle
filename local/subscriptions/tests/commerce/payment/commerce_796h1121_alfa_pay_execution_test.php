<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\availability\CommercePaymentAvailabilityResolver;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\payment\provider\alfa\AlfaPayPaymentGateway;
use local_subscriptions\payment\alfa\AlfaFastPaymentGatewayInterface;
use local_subscriptions\payment\alfa\AlfaGateway;

final class commerce_796h1121_alfa_pay_execution_test extends advanced_testcase {
    public function test_native_alfa_gateway_exposes_fast_payment_contract(): void {
        $this->assertTrue(
            interface_exists(AlfaFastPaymentGatewayInterface::class)
        );
        $this->assertTrue(
            is_a(
                AlfaGateway::class,
                AlfaFastPaymentGatewayInterface::class,
                true
            )
        );
        $this->assertTrue(
            method_exists(
                AlfaGateway::class,
                'create_alfapay_session'
            )
        );
        $this->assertTrue(
            interface_exists(AlfaPayPaymentGateway::class)
        );
        $this->assertSame(
            'alfa_pay',
            CommercePaymentMethod::ALFA_PAY
        );
    }

    public function test_native_alfa_gateway_matches_official_alfapay_response_shape(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $this->assertStringContainsString(
            '/alfapay/payment.do',
            $source
        );
        $this->assertStringContainsString(
            '$this->post_json(',
            $source
        );
        $this->assertStringContainsString(
            "'currencyCode' => 643",
            $source
        );
        $this->assertStringContainsString(
            "\$response['data']",
            $source
        );
        $this->assertStringContainsString(
            "\$data['orderId']",
            $source
        );
        $this->assertStringContainsString(
            "\$data['redirect']",
            $source
        );
    }

    public function test_alfapay_uses_dedicated_api_credentials_not_operator_credentials(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $start = strpos($source, 'public function create_alfapay_session');
        $end = strpos($source, 'private function alfapay_endpoint', $start);
        $alfapay = substr($source, $start, $end - $start);

        $this->assertStringContainsString(
            '$this->post_json(',
            $alfapay
        );
        $this->assertStringContainsString(
            '$this->api_username()',
            $alfapay
        );
        $this->assertStringContainsString(
            '$this->api_password()',
            $alfapay
        );
        $this->assertStringNotContainsString(
            "'merchant_userpass'",
            $alfapay
        );

        $this->assertStringContainsString(
            'private function api_username(): ?string',
            $source
        );
        $this->assertStringContainsString(
            'return $this->refundusername;',
            $source
        );
        $this->assertStringContainsString(
            'private function api_password(): ?string',
            $source
        );
        $this->assertStringContainsString(
            'return $this->refundpassword;',
            $source
        );
    }

    public function test_checkout_presenter_uses_uploaded_alfapay_brand_asset(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/pix/providers/alfapay.svg'
        );

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/CommerceCheckoutPaymentMethodPresenter.php'
        );
        $this->assertStringContainsString(
            "CommercePaymentMethod::ALFA_PAY => 'alfapay.svg'",
            $presenter
        );
    }

    public function test_registry_exposes_alfa_pay_to_rub_checkout_when_admin_enabled(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('alfa_env', 'live', 'local_subscriptions');
        set_config(
            'alfa_live_api_base',
            'https://pay.alfabank.ru/payment/rest',
            'local_subscriptions'
        );
        set_config(
            'alfa_live_username',
            'r-campusfr-operator',
            'local_subscriptions'
        );
        set_config(
            'alfa_live_password',
            'operator-password',
            'local_subscriptions'
        );
        set_config(
            'alfa_live_refund_username',
            'r-campusfr-api',
            'local_subscriptions'
        );
        set_config(
            'alfa_live_refund_password',
            'api-password',
            'local_subscriptions'
        );
        set_config(
            'commerce_presented_payment_providers',
            'alfa',
            'local_subscriptions'
        );
        set_config(
            'commerce_presented_payment_methods',
            'card,alfa_pay',
            'local_subscriptions'
        );

        $registry = CommercePaymentProviderRegistryFactory::create($DB);
        $provider = $registry->get('alfa');

        $this->assertContains(
            CommercePaymentMethod::ALFA_PAY,
            $provider->get_capabilities()->get_payment_methods()
        );

        $availability = new CommercePaymentAvailabilityResolver($registry);
        $alfapay = $availability->method(
            'RUB',
            CommercePaymentMethod::ALFA_PAY,
            'FR'
        );

        $this->assertTrue($alfapay->is_available());
        $this->assertSame(
            'alfa',
            $alfapay->get_preferred_provider_key()
        );
    }

    public function test_alfapay_uses_paypal_style_branding_and_architecture_icon(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $architecture = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        );

        $this->assertStringContainsString(
            '\'isalfapay\' => $method === CommercePaymentMethod::ALFA_PAY',
            $presenter
        );
        $this->assertStringContainsString('alfapay_logo.png', $checkout);
        $this->assertStringContainsString(
            "'alfapaylogourl' =>",
            $checkout
        );
        $this->assertStringContainsString(
            'alfapay_logo.png',
            $checkout
        );
        $this->assertMatchesRegularExpression(
            "/'alfa_pay'\\s*=>\\s*\\[\\s*'file'\\s*=>\\s*'alfapay\\.svg'/s",
            $architecture
        );
    }

    public function test_alfapay_rest_language_is_normalized_to_uppercase(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $this->assertStringContainsString("? 'RU'", $source);
        $this->assertStringContainsString(": 'EN'", $source);
        $this->assertStringContainsString(
            '[CampusFR][AlfaPay] initialization rejected:',
            $source
        );
        $this->assertStringNotContainsString(
            '\'password\' => $this->password',
            substr(
                $source,
                strpos($source, '[CampusFR][AlfaPay] initialization rejected:'),
                1200
            )
        );
    }

    public function test_alternative_payment_cards_keep_the_same_copy_column_as_primary_cards(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            'commerce-checkout-action-card__copy',
            $template
        );
        $this->assertStringContainsString(
            'commerce-checkout-action-card__secondary-content',
            $template
        );
        $this->assertStringContainsString(
            '{{#primaryactioncards}}',
            $template
        );
        $this->assertStringContainsString(
            '{{#secondaryactioncards}}',
            $template
        );
    }

    public function test_alfapay_uses_json_and_r_prefixed_live_host(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "'https://payment.alfabank.ru/alfapay/payment.do'",
            $source
        );
        self::assertStringContainsString(
            "'https://pay.alfabank.ru/alfapay/payment.do'",
            $source
        );
        self::assertStringContainsString(
            "str_starts_with(\$apiusername, 'r-')",
            $source
        );
        self::assertStringContainsString(
            "'Content-Type: application/json'",
            $source
        );
        self::assertStringContainsString(
            '$this->post_json(',
            $source
        );
        self::assertStringContainsString(
            "'userName' => \$apiusername",
            $source
        );
        self::assertStringContainsString(
            "'password' => \$apipassword",
            $source
        );
    }

}
