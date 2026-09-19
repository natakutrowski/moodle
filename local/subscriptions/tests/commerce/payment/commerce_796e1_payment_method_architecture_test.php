<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e1_payment_method_architecture_test extends \advanced_testcase {

    public function test_capabilities_keep_method_identity_separate_from_provider_identity(): void {
        $capabilities = new \local_subscriptions\commerce\payment\provider\CommercePaymentProviderCapabilities(
            ['EUR', 'USD'], true, false, true, true, true,
            ['provider' => 'example'],
            ['card', 'apple_pay', 'google_pay']
        );
        self::assertTrue($capabilities->supports_payment_method('card'));
        self::assertTrue($capabilities->supports_payment_method('apple_pay'));
        self::assertSame(['card', 'apple_pay', 'google_pay'], $capabilities->get_payment_methods());
        self::assertSame('example', $capabilities->get_metadata()['provider']);
    }


    public function test_payment_request_can_prefer_method_without_preferring_provider(): void {
        $request = new \local_subscriptions\commerce\payment\CommercePaymentRequest(
            'PAY.E1',
            new \local_subscriptions\commerce\payment\CommercePaymentCustomer(null, 'e1@example.test', 'E1', 'Test'),
            [new \local_subscriptions\commerce\payment\CommercePaymentLine('SKU.E1', 'E1', 1, 3000, 'EUR')],
            'EUR', 3000, null,
            'https://example.test/return',
            'https://example.test/cancel',
            [], null, 'apple_pay'
        );
        self::assertNull($request->get_preferred_provider());
        self::assertSame('apple_pay', $request->get_preferred_payment_method());
    }


    public function test_current_providers_expose_their_certified_payment_method_sets(): void {
        global $CFG;

        $stripe = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/stripe/StripeCommercePaymentProvider.php'
        );
        self::assertIsString($stripe);
        global $CFG;

        $alfa = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/alfa/AlfaCommercePaymentProvider.php'
        );
        self::assertIsString($alfa);

        foreach (['CARD', 'APPLE_PAY', 'GOOGLE_PAY', 'LINK', 'KLARNA'] as $method) {
            self::assertStringContainsString('CommercePaymentMethod::' . $method, $stripe);
        }
        self::assertStringContainsString('CommercePaymentMethod::CARD', $alfa);
        self::assertStringContainsString('CommercePaymentMethod::ALFA_PAY', $alfa);
        self::assertStringContainsString('CommercePaymentMethod::SBP', $alfa);
    }

}
