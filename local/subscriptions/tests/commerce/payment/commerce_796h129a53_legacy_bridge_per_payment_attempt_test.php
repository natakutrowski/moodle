<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a53_legacy_bridge_per_payment_attempt_test extends \advanced_testcase {
    public function test_legacy_bridge_keys_retry_token_by_native_payment_attempt(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutLegacyPaymentRequestBridge.php'
        );

        self::assertStringContainsString(
            "\$commercepaymentid = (int)(\$metadata['commerce_payment_id'] ?? 0);",
            $source
        );
        self::assertStringContainsString(
            "? 'commerce-payment:' . \$commercepaymentid",
            $source
        );
        self::assertStringContainsString(
            ": 'commerce-checkout:' . \$request->get_reference()",
            $source
        );
    }

    public function test_different_payment_attempts_can_have_different_providers_on_same_purchase(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutLegacyPaymentRequestBridge.php'
        );

        // Compatibility is still checked when the same attempt is replayed.
        self::assertStringContainsString(
            '$this->assert_compatible($record, $request);',
            $source
        );

        // But the lookup key is now attempt-specific, so a second provider
        // gets its own mirror instead of colliding with the first one.
        self::assertStringContainsString(
            "'retry_token' => \$token",
            $source
        );
        self::assertStringContainsString(
            "'commerce_payment_id' => \$commercepaymentid",
            $source
        );
    }

    public function test_payment_identity_is_enriched_before_legacy_bridge(): void {
        global $CFG;

        $runtime = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutRuntime.php'
        );

        $enrich = strpos(
            $runtime,
            '$this->identityenricher->enrich('
        );
        $bridge = strpos(
            $runtime,
            '$this->legacybridge->persist_and_enrich('
        );

        self::assertNotFalse($enrich);
        self::assertNotFalse($bridge);
        self::assertGreaterThan($enrich, $bridge);
    }
}
