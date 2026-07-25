<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\purchase\NativePurchase;
use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseItem;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\domain\value\CommercePurchaseSnapshot;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Restores the CRM read model from one native SQL aggregate. */
final class NativeCrmCommercePurchaseMapper {
    public function map(CommercePurchasePersistenceSnapshot $snapshot): NativePurchase {
        $purchase = $snapshot->get_purchase()->to_record();
        $customer = $this->decode((string)$purchase->customerjson);
        $commercial = $this->decode((string)$purchase->snapshotjson);
        $metadata = $this->decode((string)$purchase->metadatajson);

        $items = [];
        foreach ($snapshot->get_items() as $storeditem) {
            $record = $storeditem->to_record();
            $itemmetadata = $this->decode((string)$record->metadatajson);
            $catalogitem = new CommerceItem(
                (string)$record->itemtype,
                (string)$record->itemreference,
                (string)$record->label,
                null,
                $itemmetadata
            );
            $items[] = new CommercePurchaseItem(
                $catalogitem,
                (int)$record->quantity,
                CommerceMoney::from_minor((int)$record->unitminor, (string)$record->currency),
                CommerceMoney::from_minor((int)$record->discountminor, (string)$record->currency),
                $this->decode((string)$record->pricingjson),
                $this->decode((string)$record->fulfillmentjson),
                $itemmetadata
            );
        }

        $payments = [];
        foreach ($snapshot->get_payments() as $storedpayment) {
            $record = $storedpayment->to_record();
            $payments[] = new CommercePayment(
                (int)$record->amountminor,
                (string)$record->currency,
                (string)$record->status,
                $this->nullable_string($record->provider ?? null),
                $this->nullable_string($record->transactionid ?? null),
                $this->nullable_positive_int($record->legacyrequestid ?? null),
                $this->nullable_positive_int($record->paidat ?? null),
                $this->decode((string)$record->metadatajson)
            );
        }

        return new NativePurchase(
            (string)$purchase->type,
            CommercePurchaseId::from_string((string)$purchase->purchaseuuid),
            CommercePurchaseReference::from_string((string)$purchase->reference),
            new CommerceCustomerSnapshot(
                $this->nullable_positive_int($customer['userid'] ?? $purchase->userid ?? null),
                $this->nullable_string($customer['email'] ?? $purchase->customeremail ?? null),
                $this->nullable_string($customer['firstname'] ?? null),
                $this->nullable_string($customer['lastname'] ?? null),
                $this->nullable_string($customer['country'] ?? null),
                $this->nullable_string($customer['language'] ?? null),
                is_array($customer['metadata'] ?? null) ? $customer['metadata'] : []
            ),
            $items,
            new CommercePurchaseSnapshot(
                (string)($commercial['offerreference'] ?? $purchase->reference),
                (string)($commercial['offerlabel'] ?? $items[0]->get_label()),
                $this->nullable_string($commercial['offerversion'] ?? null),
                is_array($commercial['pricingcontext'] ?? null) ? $commercial['pricingcontext'] : [],
                is_array($commercial['fulfillmentcontext'] ?? null) ? $commercial['fulfillmentcontext'] : [],
                is_array($commercial['terms'] ?? null) ? $commercial['terms'] : [],
                is_array($commercial['metadata'] ?? null) ? $commercial['metadata'] : []
            ),
            (string)$purchase->status,
            $payments,
            [],
            $this->nullable_positive_int($purchase->timecreated ?? null),
            $this->nullable_positive_int($purchase->timemodified ?? null),
            $metadata
        );
    }

    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
    private function nullable_string(mixed $value): ?string {
        if ($value === null) { return null; }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
    private function nullable_positive_int(mixed $value): ?int {
        if ($value === null || $value === '') { return null; }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
