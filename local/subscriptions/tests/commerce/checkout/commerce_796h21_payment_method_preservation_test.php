<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h21_payment_method_preservation_test extends advanced_testcase {
    public function test_identity_enricher_preserves_preferred_payment_method(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutPaymentIdentityEnricher.php'
        );

        $this->assertStringContainsString(
            '$request->get_preferred_payment_method()',
            $contents
        );

        $this->assertStringContainsString(
            '$request->get_created_at(),' . PHP_EOL
            . '            $request->get_preferred_payment_method()',
            $contents
        );
    }

    public function test_legacy_bridge_preserves_preferred_payment_method(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutLegacyPaymentRequestBridge.php'
        );

        $this->assertStringContainsString(
            '$request->get_preferred_payment_method()',
            $contents
        );

        $this->assertStringContainsString(
            '$request->get_created_at(),' . PHP_EOL
            . '            $request->get_preferred_payment_method()',
            $contents
        );
    }

    public function test_stripe_embedded_selection_depends_on_preserved_method(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'StripeCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            '$request->get_preferred_payment_method()',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentMethod::CARD',
            $contents
        );
        $this->assertStringContainsString(
            'create_payment_intent(',
            $contents
        );
    }
}
