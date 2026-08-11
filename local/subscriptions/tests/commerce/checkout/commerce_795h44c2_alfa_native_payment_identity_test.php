<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Verifies that Alfa exposes only the Native Commerce identity.
 *
 * @covers \local_subscriptions\commerce\payment\provider\alfa\LegacyAlfaPaymentGateway
 */
final class commerce_795h44c2_alfa_native_payment_identity_test extends advanced_testcase {
    public function test_alfa_outward_metadata_uses_native_identity(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/payment/provider/alfa/LegacyAlfaPaymentGateway.php'
        );

        $this->assertIsString($source);

        $methodstart = strpos($source, 'private function map_checkout_result');
        $methodend = strpos($source, 'private function required_native_identity', $methodstart);
        $method = substr($source, $methodstart, $methodend - $methodstart);

        $this->assertStringContainsString("'commerce_payment_id'", $method);
        $this->assertStringContainsString("'commerce_purchase_uuid'", $method);
        $this->assertStringContainsString("'commerce_reference'", $method);
        $this->assertStringContainsString("'alfa_order_id'", $method);

        $this->assertStringNotContainsString("'legacy_payment_request_id'", $method);
        $this->assertStringNotContainsString("'legacy_payment_request_table'", $method);
        $this->assertStringNotContainsString("'legacy_payment_context'", $method);
        $this->assertStringNotContainsString("'legacy_order_number_prefix'", $method);
    }
}
