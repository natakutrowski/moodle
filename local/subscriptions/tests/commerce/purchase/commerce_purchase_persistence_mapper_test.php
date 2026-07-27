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
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;

final class commerce_purchase_persistence_mapper_test extends \advanced_testcase {
    public function test_complete_aggregate_is_mapped_without_database_access(): void {
        $id = CommercePurchaseId::from_string(str_repeat('a', 32));
        $purchase = new NativePurchase(
            'bundle',
            $id,
            CommercePurchaseReference::from_purchase_id($id),
            new CommerceCustomerSnapshot(96, 'student@example.com', 'Ada', 'Lovelace', 'FR', 'fr'),
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
            new CommercePurchaseSnapshot('bundle:a2-premium', 'Pack A2 Premium', '2026.1'),
            CommercePurchaseStatus::DRAFT,
            [new CommercePayment(19000, 'EUR', CommercePayment::STATUS_PENDING, 'stripe')],
            [new CommerceFulfillmentOperation(
                'bundle:test',
                'course_access',
                ['persistence_status' => 'completed', 'source' => 'test']
            )],
            1700000000,
            1700000100,
            ['campaign' => 'summer']
        );

        $snapshot = (new CommercePurchasePersistenceMapper())->map($purchase);

        $this->assertSame(str_repeat('a', 32), $snapshot->get_purchase()->get_purchase_uuid());
        $this->assertSame(19000, $snapshot->get_purchase()->get_total_minor());
        $this->assertCount(2, $snapshot->get_items());
        $this->assertCount(1, $snapshot->get_payments());
        $this->assertCount(1, $snapshot->get_fulfillments());
        $this->assertSame(0, $snapshot->get_items()[0]->get_position());
        $this->assertSame(0, $snapshot->get_payments()[0]->get_sequence());

        $fulfillmentrecord = $snapshot->get_fulfillments()[0]->to_record();
        $this->assertSame('completed', $fulfillmentrecord->status);
        $this->assertStringNotContainsString('persistence_status', $fulfillmentrecord->metadatajson);
        $this->assertStringContainsString('source', $fulfillmentrecord->metadatajson);
    }
}
