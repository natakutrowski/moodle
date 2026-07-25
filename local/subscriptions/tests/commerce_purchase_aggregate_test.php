<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\purchase\NativePurchase;
use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;

final class commerce_purchase_aggregate_test extends \advanced_testcase {
    public function test_native_bundle_supports_multiple_items_payments_and_fulfillments(): void {
        $id = CommercePurchaseId::generate();
        $purchase = new NativePurchase(
            'bundle',
            $id,
            CommercePurchaseReference::from_purchase_id($id),
            new CommerceCustomerSnapshot(96, 'student@example.com'),
            [
                new CommercePurchaseItem(
                    new CommerceItem(CommerceItem::TYPE_SUBSCRIPTION, 'course:a2', 'Cours A2', 17),
                    1,
                    CommerceMoney::from_minor(16000, 'EUR')
                ),
                new CommercePurchaseItem(
                    new CommerceItem(CommerceItem::TYPE_DIGITAL, 'digital:grammar', 'PDF grammaire', 8),
                    1,
                    CommerceMoney::from_minor(3900, 'EUR'),
                    CommerceMoney::from_minor(900, 'EUR')
                ),
            ],
            new CommercePurchaseSnapshot('bundle:a2-premium', 'Pack A2 Premium'),
            CommercePurchaseStatus::DRAFT
        );

        $this->assertCount(2, $purchase->get_items());
        $this->assertSame(19000, $purchase->get_totals()->get_total()->get_amount_minor());
        $this->assertCount(0, $purchase->get_payments());

        $purchase->prepare();
        $purchase->mark_payment_pending();
        $purchase->add_payment(new CommercePayment(19000, 'EUR', CommercePayment::STATUS_COMPLETED));
        $purchase->mark_paid();
        $purchase->add_fulfillment(new CommerceFulfillmentOperation('bundle:test', 'course_access'));
        $purchase->add_fulfillment(new CommerceFulfillmentOperation('bundle:test', 'digital_access'));
        $purchase->mark_fulfillment_pending();
        $purchase->mark_fulfilled();
        $purchase->complete();

        $this->assertTrue($purchase->is_paid());
        $this->assertCount(2, $purchase->get_fulfillments());
        $this->assertSame(CommercePurchaseStatus::COMPLETED, $purchase->get_lifecycle_status());
    }

    public function test_rejects_invalid_transition_and_duplicate_fulfillment(): void {
        $id = CommercePurchaseId::generate();
        $purchase = new NativePurchase(
            'digital',
            $id,
            CommercePurchaseReference::from_purchase_id($id),
            new CommerceCustomerSnapshot(null, 'guest@example.com'),
            [new CommercePurchaseItem(
                new CommerceItem(CommerceItem::TYPE_DIGITAL, 'digital:test', 'Test', 1),
                1,
                CommerceMoney::from_minor(1000, 'EUR')
            )],
            new CommercePurchaseSnapshot('digital:test', 'Test'),
            CommercePurchaseStatus::DRAFT
        );

        $operation = new CommerceFulfillmentOperation('purchase:test', 'digital_access');
        $purchase->add_fulfillment($operation);

        $this->expectException(\coding_exception::class);
        $purchase->add_fulfillment($operation);
    }
}
