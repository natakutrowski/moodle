<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** C5.14.3 — Buy Now must survive Guest OTP -> existing-account login. */
final class commerce_798c5143_guest_direct_purchase_existing_account_resume_test extends \advanced_testcase {
    public function test_resume_restores_direct_purchase_before_checkout_redirect(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString(
            'CommercePurchaseFlow::normalise(',
            $source
        );
        self::assertStringContainsString(
            'CommercePurchaseFlow::is_direct($purchaseflow)',
            $source
        );
        self::assertStringContainsString(
            '$directpurchase = $metadata[\'direct_purchase\'] ?? null;',
            $source
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::store(',
            $source
        );
        self::assertStringContainsString(
            "'direct_purchase_restored_at'",
            $source
        );
        self::assertStringContainsString(
            'if ($isdirectpurchase) {' . "\n" . '    redirect($checkouturl);',
            $source
        );
    }

    public function test_direct_resume_does_not_use_empty_normal_cart_as_gate(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );
        self::assertIsString($source);

        $directredirect = strpos(
            $source,
            'if ($isdirectpurchase) {' . "\n" . '    redirect($checkouturl);'
        );
        $normalcartsnapshot = strpos(
            $source,
            '$snapshot = CommerceCartRuntimeFactory::create()->snapshot('
        );

        self::assertNotFalse($directredirect);
        self::assertNotFalse($normalcartsnapshot);
        self::assertLessThan(
            $normalcartsnapshot,
            $directredirect,
            'Direct Purchase must redirect before the normal-cart empty check.'
        );
    }

    public function test_direct_payload_is_validated_against_guest_currency_and_catalogue_identity(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString(
            '$directcurrency !== $currency',
            $source
        );
        self::assertStringContainsString(
            '$directsku === \'\'',
            $source
        );
        self::assertStringContainsString(
            '$directpriceid <= 0',
            $source
        );
        self::assertStringContainsString(
            '$directquantity <= 0',
            $source
        );
        self::assertStringContainsString(
            "preg_match('/^[a-f0-9]{32}$/', strtolower(\$directcartuuid))",
            $source
        );
    }
}
