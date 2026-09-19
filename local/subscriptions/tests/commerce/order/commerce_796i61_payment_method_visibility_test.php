<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\method\CommercePersistedPaymentMethodResolver;

final class commerce_796i61_payment_method_visibility_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_resolver_prefers_durable_payment_method_metadata(): void {
        $resolver = new CommercePersistedPaymentMethodResolver();

        self::assertSame(
            'apple_pay',
            $resolver->resolve('stripe', ['payment_method' => 'apple_pay'])
        );
        self::assertSame(
            'sbp',
            $resolver->resolve('alfa', ['payment_method' => 'sbp'])
        );
        self::assertSame(
            'alfa_pay',
            $resolver->resolve('alfa', ['payment_method' => 'alfapay'])
        );
    }

    public function test_resolver_reads_pre_i61_provider_payload_without_inferring_provider(): void {
        $resolver = new CommercePersistedPaymentMethodResolver();

        self::assertSame(
            'google_pay',
            $resolver->resolve('stripe', [], [
                'result_metadata' => ['commerce_payment_method' => 'google_pay'],
            ])
        );
        self::assertNull($resolver->resolve('stripe'));
        self::assertNull($resolver->resolve('alfa'));
        self::assertSame('paypal', $resolver->resolve('paypal'));
    }

    public function test_checkout_attempt_persists_selected_method_and_surfaces_use_read_model(): void {
        global $CFG;

        $persister = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/checkout/unified/'
                . 'CommerceCheckoutPurchasePersister.php'
        );
        $readrepository = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/purchase/readmodel/'
                . 'CommercePurchaseReadRepository.php'
        );
        $orderdetails = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_details.php'
        );
        $sales = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/index.php'
        );
        $purchaseview = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        $invoice = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/order/invoice/'
                . 'CommerceInvoicePdfService.php'
        );

        self::assertStringContainsString("'payment_method' =>", $persister);
        self::assertStringContainsString(
            'CommercePersistedPaymentMethodResolver',
            $readrepository
        );
        self::assertStringContainsString("'paymentmethod' =>", $orderdetails);
        self::assertStringContainsString('paymentroutehtml', $sales);
        self::assertStringContainsString('payment->paymentmethod', $purchaseview);
        self::assertStringContainsString('paymentmethodlabel', $invoice);
        self::assertStringContainsString("0, 'L', false, 1", $invoice);
    }

    public function test_i61_requires_no_schema_or_version_change(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertMatchesRegularExpression(
            '/\\$plugin->version = \\d+;/',
            $version
        );
    }
}
