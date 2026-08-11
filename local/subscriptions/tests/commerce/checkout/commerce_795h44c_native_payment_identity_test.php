<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPaymentIdentityEnricher;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;

/**
 * @covers \local_subscriptions\commerce\checkout\unified\CommerceCheckoutPaymentIdentityEnricher
 */
final class commerce_795h44c_native_payment_identity_test extends advanced_testcase {
    public function test_native_identity_is_added_to_metadata_and_browser_urls(): void {
        $request = new CommercePaymentRequest(
            'cmp_h44c_reference',
            new CommercePaymentCustomer(42, 'h44c@example.test'),
            [new CommercePaymentLine('a1-full', 'A1 Full', 1, 26400, 'EUR')],
            'EUR',
            26400,
            'stripe',
            'https://example.test/local/subscriptions/payment/return.php',
            'https://example.test/local/subscriptions/payment_cancel.php?source=checkout',
            ['source' => 'unified_checkout'],
            time()
        );
        $attempt = new CommercePaymentAttempt(
            472,
            '1123456789abcdef0123456789abcdef',
            0,
            'stripe',
            'created',
            26400,
            'EUR'
        );

        $enriched = (new CommerceCheckoutPaymentIdentityEnricher())->enrich(
            $request,
            $attempt
        );

        $this->assertSame(472, $enriched->get_metadata_value('commerce_payment_id'));
        $this->assertSame(
            '1123456789abcdef0123456789abcdef',
            $enriched->get_metadata_value('commerce_purchase_uuid')
        );
        $this->assertSame(0, $enriched->get_metadata_value('commerce_payment_sequence'));
        $this->assertStringContainsString('paymentid=472', (string)$enriched->get_return_url());
        $this->assertStringContainsString(
            'purchaseuuid=1123456789abcdef0123456789abcdef',
            (string)$enriched->get_return_url()
        );
        $this->assertStringContainsString('source=checkout', (string)$enriched->get_cancel_url());
        $this->assertStringContainsString('paymentid=472', (string)$enriched->get_cancel_url());
    }

    public function test_stripe_outward_metadata_uses_native_identity(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/payment/provider/stripe/LegacyStripePaymentGateway.php'
        );

        $this->assertIsString($source);

        $methodstart = strpos($source, 'private function build_stripe_metadata');
        $methodend = strpos($source, 'private function build_product_name', $methodstart);
        $method = substr($source, $methodstart, $methodend - $methodstart);

        $this->assertStringContainsString("'commerce_payment_id'", $method);
        $this->assertStringContainsString("'commerce_purchase_uuid'", $method);
        $this->assertStringNotContainsString("'legacy_payment_request_id'", $method);
        $this->assertStringNotContainsString("'legacy_payment_request_table'", $method);
    }
}
