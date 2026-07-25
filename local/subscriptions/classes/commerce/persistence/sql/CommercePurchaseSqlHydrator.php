<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\persistence\sql;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommerceFulfillmentRecord;
use local_subscriptions\commerce\persistence\record\CommercePaymentRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;

final class CommercePurchaseSqlHydrator {

    /**
     * @param \stdClass[] $items
     * @param \stdClass[] $payments
     * @param \stdClass[] $fulfillments
     */
    public function hydrate(
        \stdClass $purchase,
        array $items,
        array $payments,
        array $fulfillments
    ): CommercePurchasePersistenceSnapshot {
        $purchaseuuid = (string) $purchase->purchaseuuid;

        return new CommercePurchasePersistenceSnapshot(
            $this->hydrate_purchase($purchase),
            $this->hydrate_items($purchaseuuid, $items),
            $this->hydrate_payments($purchaseuuid, $payments),
            $this->hydrate_fulfillments($purchaseuuid, $fulfillments)
        );
    }

    private function hydrate_purchase(
        \stdClass $record
    ): CommercePurchaseRecord {
        return new CommercePurchaseRecord(
            (string) $record->purchaseuuid,
            (string) $record->reference,
            (string) $record->type,
            $this->nullable_string($record->legacyfamily ?? null),
            $this->nullable_positive_int($record->legacyid ?? null),
            $this->nullable_positive_int($record->userid ?? null),
            $this->nullable_string($record->customeremail ?? null),
            (string) $record->status,
            (string) $record->currency,
            (int) $record->subtotalminor,
            (int) $record->discountminor,
            (int) $record->totalminor,
            (string) $record->customerjson,
            (string) $record->snapshotjson,
            (string) $record->metadatajson,
            (int) $record->snapshotversion,
            $this->nullable_timestamp($record->timecreated ?? null),
            $this->nullable_timestamp($record->timemodified ?? null)
        );
    }    

    /**
     * @param \stdClass[] $records
     * @return CommercePurchaseItemRecord[]
     */
    private function hydrate_items(
        string $purchaseuuid,
        array $records
    ): array {
        $items = [];

        foreach ($records as $record) {
            $items[] = new CommercePurchaseItemRecord(
                $purchaseuuid,
                (int) $record->position,
                (string) $record->itemtype,
                (string) $record->itemreference,
                (string) $record->label,
                (int) $record->quantity,
                (string) $record->currency,
                (int) $record->unitminor,
                (int) $record->grossminor,
                (int) $record->discountminor,
                (int) $record->netminor,
                (string) $record->pricingjson,
                (string) $record->fulfillmentjson,
                (string) $record->metadatajson
            );
        }

        return $items;
    }

    /**
     * @param \stdClass[] $records
     * @return CommercePaymentRecord[]
     */
    private function hydrate_payments(
        string $purchaseuuid,
        array $records
    ): array {
        $payments = [];

        foreach ($records as $record) {
            $payments[] = new CommercePaymentRecord(
                $purchaseuuid,
                (int) $record->sequence,
                $this->nullable_string($record->provider ?? null),
                $this->nullable_string(
                    $record->providerreference ?? null
                ),
                (string) $record->status,
                (string) $record->currency,
                (int) $record->amountminor,
                $this->nullable_string($record->transactionid ?? null),
                $this->nullable_positive_int(
                    $record->legacyrequestid ?? null
                ),
                $this->nullable_timestamp($record->paidat ?? null),
                (string) $record->metadatajson
            );
        }

        return $payments;
    }

    /**
     * @param \stdClass[] $records
     * @return CommerceFulfillmentRecord[]
     */
    private function hydrate_fulfillments(
        string $purchaseuuid,
        array $records
    ): array {
        $fulfillments = [];

        foreach ($records as $record) {
            $fulfillments[] = new CommerceFulfillmentRecord(
                $purchaseuuid,
                (int) $record->sequence,
                (string) $record->reference,
                (string) $record->fulfillmentkey,
                (string) $record->idempotencykey,
                (string) $record->status,
                (string) $record->metadatajson
            );
        }

        return $fulfillments;
    }

    private function nullable_string(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullable_positive_int(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function nullable_timestamp(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

}