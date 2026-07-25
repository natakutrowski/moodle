<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\persistence\sql;

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommerceFulfillmentRecord;
use local_subscriptions\commerce\persistence\record\CommercePaymentRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;
use moodle_database;

final class CommercePurchaseSqlWriter {
    public function __construct(
        private readonly moodle_database $database
    ) {
    }

    public function write(
        CommercePurchasePersistenceSnapshot $snapshot
    ): int {
        $transaction = $this->database->start_delegated_transaction();

        try {
            $purchaseid = $this->upsert_purchase(
                $snapshot->get_purchase()
            );

            $this->replace_items(
                $purchaseid,
                $snapshot->get_items()
            );

            $this->replace_payments(
                $purchaseid,
                $snapshot->get_payments()
            );

            $this->replace_fulfillments(
                $purchaseid,
                $snapshot->get_fulfillments()
            );

            $transaction->allow_commit();

            return $purchaseid;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);

            throw $exception;
        }
    }   


    /**
     * Delete one complete native aggregate by its Legacy identity.
     *
     * Children are deleted explicitly so this remains safe even if a target
     * database does not enforce cascading foreign keys.
     */
    public function delete_by_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): bool {
        $legacyfamily = trim($legacyfamily);
        if ($legacyfamily === '' || $legacyid <= 0) {
            return false;
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'legacyfamily' => $legacyfamily,
                'legacyid' => $legacyid,
            ],
            'id',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return false;
        }

        $transaction = $this->database->start_delegated_transaction();
        try {
            $purchaseid = (int)$purchase->id;
            foreach ([
                CommercePersistenceSchema::TABLE_ITEM,
                CommercePersistenceSchema::TABLE_PAYMENT,
                CommercePersistenceSchema::TABLE_FULFILLMENT,
            ] as $table) {
                $this->database->delete_records($table, ['purchaseid' => $purchaseid]);
            }
            $this->database->delete_records(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['id' => $purchaseid]
            );
            $transaction->allow_commit();
            return true;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }

    private function upsert_purchase(
        CommercePurchaseRecord $record
    ): int {
        $existing = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'purchaseuuid' => $record->get_purchase_uuid(),
            ],
            'id',
            IGNORE_MISSING
        );

        $now = time();

        $data = $this->purchase_record_to_database(
            $record,
            $existing ? (int) $existing->id : null,
            $now
        );

        if ($existing) {
            $data->id = (int) $existing->id;

            $this->database->update_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                $data
            );

            return (int) $existing->id;
        }

        return (int) $this->database->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            $data
        );
    }

    private function purchase_record_to_database(
        CommercePurchaseRecord $record,
        ?int $existingid,
        int $now
    ): \stdClass {

        $data = $record->to_record();

        $timecreated = $data->timecreated;

        if ($timecreated === null || $timecreated <= 0) {
            $timecreated = $now;
        }

        if ($existingid !== null) {
            $existing = $this->database->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                ['id' => $existingid],
                'timecreated',
                MUST_EXIST
            );

            $timecreated = (int) $existing->timecreated;
        }

        $timemodified = $data->timemodified;

        if ($timemodified === null || $timemodified <= 0) {
            $timemodified = $now;
        }

        $data->timecreated = $timecreated;
        $data->timemodified = $timemodified;

        return $data;
    }

    /**
     * @param CommercePurchaseItemRecord[] $records
     */
    private function replace_items(
        int $purchaseid,
        array $records
    ): void {
        $this->database->delete_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $purchaseid]
        );

        foreach ($records as $record) {
            if (!$record instanceof CommercePurchaseItemRecord) {
                throw new \coding_exception(
                    'Commerce Purchase items must contain only '
                        . CommercePurchaseItemRecord::class . ' instances.'
                );
            }

            $data = $record->to_record();

            unset($data->purchaseuuid);

            $data->purchaseid = $purchaseid;

            $this->database->insert_record(
                CommercePersistenceSchema::TABLE_ITEM,
                $data
            );
        }
    }

    /**
     * @param CommercePaymentRecord[] $records
     */
    private function replace_payments(
        int $purchaseid,
        array $records
    ): void {
        $this->database->delete_records(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => $purchaseid]
        );

        foreach ($records as $record) {
            if (!$record instanceof CommercePaymentRecord) {
                throw new \coding_exception(
                    'Commerce payments must contain only '
                        . CommercePaymentRecord::class . ' instances.'
                );
            }

            $data = $record->to_record();

            unset($data->purchaseuuid);

            $data->purchaseid = $purchaseid;

            $this->database->insert_record(
                CommercePersistenceSchema::TABLE_PAYMENT,
                $data
            );
        }
    }

    /**
     * @param CommerceFulfillmentRecord[] $records
     */
    private function replace_fulfillments(
        int $purchaseid,
        array $records
    ): void {
        $this->database->delete_records(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            ['purchaseid' => $purchaseid]
        );

        foreach ($records as $record) {
            if (!$record instanceof CommerceFulfillmentRecord) {
                throw new \coding_exception(
                    'Commerce fulfillments must contain only '
                        . CommerceFulfillmentRecord::class . ' instances.'
                );
            }

            $data = $record->to_record();

            unset($data->purchaseuuid);

            $data->purchaseid = $purchaseid;

            $this->database->insert_record(
                CommercePersistenceSchema::TABLE_FULFILLMENT,
                $data
            );
        }
    }

}