<?php

namespace local_subscriptions\commerce\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\persistence\record\CommerceFulfillmentRecord;
use local_subscriptions\commerce\persistence\record\CommercePaymentRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

/** Maps a Commerce aggregate to an atomic persistence snapshot. */
final class CommercePurchasePersistenceMapper {

    public function __construct(
        private readonly CommercePersistenceJsonCodec $jsoncodec = new CommercePersistenceJsonCodec()
    ) {
    }

    public function map(CommercePurchase $purchase): CommercePurchasePersistenceSnapshot {
        $uuid = $purchase->get_purchase_id()->get_value();
        $legacy = $purchase->get_legacy_reference();
        $totals = $purchase->get_totals();

        $purchaserecord = new CommercePurchaseRecord(
            $uuid,
            $purchase->get_purchase_reference()->get_value(),
            $purchase->get_type(),
            $legacy?->get_family(),
            $legacy?->get_legacy_id(),
            $purchase->get_customer()->get_user_id(),
            $purchase->get_customer()->get_email(),
            $purchase->get_lifecycle_status(),
            $totals->get_currency(),
            $totals->get_subtotal()->get_amount_minor(),
            $totals->get_discount()->get_amount_minor(),
            $totals->get_total()->get_amount_minor(),
            $this->jsoncodec->encode($purchase->get_customer()->to_array()),
            $this->jsoncodec->encode($purchase->get_snapshot()->to_array()),
            $this->jsoncodec->encode($purchase->get_metadata()),
            CommercePersistenceSchema::SNAPSHOT_VERSION,
            $purchase->get_created_at(),
            $purchase->get_updated_at()
        );

        $items = [];
        foreach ($purchase->get_items() as $position => $item) {
            $items[] = new CommercePurchaseItemRecord(
                $uuid,
                $position,
                $item->get_item_type(),
                $item->get_item_reference(),
                $item->get_label(),
                $item->get_quantity(),
                $item->get_currency(),
                $item->get_unit_price()->get_amount_minor(),
                $item->get_gross_amount()->get_amount_minor(),
                $item->get_discount()->get_amount_minor(),
                $item->get_net_amount()->get_amount_minor(),
                $this->jsoncodec->encode($item->get_pricing_snapshot()),
                $this->jsoncodec->encode($item->get_fulfillment_snapshot()),
                $this->jsoncodec->encode($item->get_metadata())
            );
        }

        $payments = [];
        foreach ($purchase->get_payments() as $sequence => $payment) {
            $payments[] = new CommercePaymentRecord(
                $uuid,
                $sequence,
                $payment->get_provider(),
                $payment->get_metadata_value('providerreference'),
                $payment->get_status(),
                $payment->get_currency(),
                $payment->get_amount_minor(),
                $payment->get_transaction_id(),
                $payment->get_legacy_request_id(),
                $payment->get_paid_at(),
                $this->jsoncodec->encode($payment->get_metadata())
            );
        }

        $fulfillments = [];
        foreach ($purchase->get_fulfillments() as $sequence => $fulfillment) {
            $metadata = $fulfillment->get_metadata();
            $status = strtolower(trim((string)(
                $metadata['persistence_status']
                    ?? CommerceFulfillmentRecord::STATUS_PENDING
            )));
            unset($metadata['persistence_status']);

            $fulfillments[] = new CommerceFulfillmentRecord(
                $uuid,
                $sequence,
                $fulfillment->get_reference(),
                $fulfillment->get_key(),
                $fulfillment->get_idempotency_key(),
                $status,
                $this->jsoncodec->encode($metadata)
            );
        }

        return new CommercePurchasePersistenceSnapshot(
            $purchaserecord,
            $items,
            $payments,
            $fulfillments
        );
    }
}
