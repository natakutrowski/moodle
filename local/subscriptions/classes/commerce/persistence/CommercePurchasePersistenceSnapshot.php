<?php

namespace local_subscriptions\commerce\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\record\CommerceFulfillmentRecord;
use local_subscriptions\commerce\persistence\record\CommercePaymentRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

/** Atomic database-neutral representation of a complete Commerce aggregate. */
final class CommercePurchasePersistenceSnapshot {

    /**
     * @param CommercePurchaseItemRecord[] $items
     * @param CommercePaymentRecord[] $payments
     * @param CommerceFulfillmentRecord[] $fulfillments
     */
    public function __construct(
        private readonly CommercePurchaseRecord $purchase,
        private readonly array $items,
        private readonly array $payments,
        private readonly array $fulfillments
    ) {
        if ($items === []) {
            throw new \coding_exception('A persisted Commerce purchase requires at least one item.');
        }
        $uuid = $purchase->get_purchase_uuid();
        $this->assert_children($items, CommercePurchaseItemRecord::class, $uuid);
        $this->assert_children($payments, CommercePaymentRecord::class, $uuid);
        $this->assert_children($fulfillments, CommerceFulfillmentRecord::class, $uuid);
    }

    public function get_purchase(): CommercePurchaseRecord { return $this->purchase; }
    /** @return CommercePurchaseItemRecord[] */
    public function get_items(): array { return $this->items; }
    /** @return CommercePaymentRecord[] */
    public function get_payments(): array { return $this->payments; }
    /** @return CommerceFulfillmentRecord[] */
    public function get_fulfillments(): array { return $this->fulfillments; }

    private function assert_children(array $records, string $class, string $uuid): void {
        foreach ($records as $record) {
            if (!$record instanceof $class || $record->get_purchase_uuid() !== $uuid) {
                throw new \coding_exception('A Commerce persistence child record belongs to another aggregate.');
            }
        }
    }
}
