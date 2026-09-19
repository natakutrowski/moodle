<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/** Closure guards for 7.96C native currency generalisation. */
final class commerce_796c10_currency_generalization_closure_test extends advanced_testcase {
    public function test_native_amount_boundaries_no_longer_assume_two_minor_digits(): void {
        global $CFG;
        $files = [
            '/order_result.php',
            '/classes/commerce/storefront/upgrade/CommerceStorefrontUpgradeResolver.php',
            '/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php',
            '/classes/commerce/checkout/unified/CommerceCheckoutLegacyPaymentRequestBridge.php',
        ];
        foreach ($files as $relative) {
            $source = file_get_contents($CFG->dirroot . '/local/subscriptions' . $relative);
            self::assertIsString($source);
            self::assertStringNotContainsString('$minor / 100', $source, $relative);
            self::assertStringNotContainsString('* 100);', $source, $relative);
        }
    }

    public function test_bundle_auditors_follow_enabled_currency_registry(): void {
        global $CFG;
        foreach ([
            '/classes/commerce/bundle/audit/CommerceBundlePricingAuditor.php',
            '/classes/commerce/bundle/audit/CommerceBundlePhaseCertificationAuditor.php',
        ] as $relative) {
            $source = file_get_contents($CFG->dirroot . '/local/subscriptions' . $relative);
            self::assertIsString($source);
            self::assertStringContainsString('CommerceCurrencyRegistry', $source, $relative);
            self::assertStringNotContainsString("['EUR', 'RUB']", $source, $relative);
        }
    }
}
