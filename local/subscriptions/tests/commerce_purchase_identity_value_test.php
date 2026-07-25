<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\value\CommerceLegacyPurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;

/** @coversDefaultClass \,local_subscriptions\commerce\domain\value\CommercePurchaseId */
final class commerce_purchase_identity_value_test extends advanced_testcase {

    public function test_native_id_can_be_generated_and_restored(): void {
        $id = CommercePurchaseId::generate();
        $restored = CommercePurchaseId::from_string((string)$id);

        $this->assertSame(32, strlen((string)$id));
        $this->assertTrue($id->equals($restored));
    }

    public function test_public_reference_is_stable_for_same_id(): void {
        $id = CommercePurchaseId::from_string(
            '0123456789abcdef0123456789abcdef'
        );

        $first = CommercePurchaseReference::from_purchase_id($id);
        $second = CommercePurchaseReference::from_purchase_id($id);

        $this->assertTrue($first->equals($second));
        $this->assertMatchesRegularExpression('/^cmp_[a-f0-9]{24}$/', (string)$first);
    }

    public function test_invalid_native_id_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommercePurchaseId::from_string('not-an-id');
    }

    public function test_legacy_reference_is_not_native_identity(): void {
        $reference = CommerceLegacyPurchaseReference::for_subscription(41);

        $this->assertSame('subscription:41', $reference->get_key());
        $this->assertTrue($reference->is_subscription());
        $this->assertFalse($reference->is_digital());
    }

    public function test_non_positive_legacy_identifier_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommerceLegacyPurchaseReference::for_digital_purchase(0);
    }
}
