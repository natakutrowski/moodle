<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Finds and optionally removes payment attempts whose parent purchase no longer exists.
 *
 * Dry-run is the default. Deletion is deliberately limited to the native payment table.
 */
final class CommerceOrphanPaymentCleaner {
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const PAYMENT = 'local_subscriptions_commerce_payment';

    public function __construct(private readonly moodle_database $db) {
    }

    /** @return array<int, array<string, mixed>> */
    public function inspect(): array {
        $records = $this->db->get_records_sql(
            'SELECT pay.*
               FROM {' . self::PAYMENT . '} pay
              WHERE NOT EXISTS (
                    SELECT 1
                      FROM {' . self::PURCHASE . '} p
                     WHERE p.id = pay.purchaseid
              )
           ORDER BY pay.id ASC'
        );

        $orphans = [];
        foreach ($records as $record) {
            $orphans[] = [
                'id' => (int)$record->id,
                'purchaseid' => (int)$record->purchaseid,
                'sequence' => (int)$record->sequence,
                'provider' => (string)($record->provider ?? ''),
                'providerreference' => (string)($record->providerreference ?? ''),
                'transactionid' => (string)($record->transactionid ?? ''),
                'legacyrequestid' => isset($record->legacyrequestid) ? (int)$record->legacyrequestid : null,
                'status' => (string)$record->status,
                'currency' => (string)$record->currency,
                'amountminor' => (int)$record->amountminor,
                'safe_to_delete' => true,
            ];
        }

        return $orphans;
    }

    /**
     * @param array<int, array<string, mixed>> $orphans
     * @return array{deleted:int, skipped:int, deletedids:array<int,int>, skippedids:array<int,int>}
     */
    public function execute(array $orphans): array {
        $deletedids = [];
        $skippedids = [];

        $transaction = $this->db->start_delegated_transaction();
        foreach ($orphans as $orphan) {
            $id = (int)($orphan['id'] ?? 0);
            if ($id <= 0 || empty($orphan['safe_to_delete'])) {
                if ($id > 0) {
                    $skippedids[] = $id;
                }
                continue;
            }

            $current = $this->db->get_record(self::PAYMENT, ['id' => $id], '*', IGNORE_MISSING);
            if (!$current) {
                continue;
            }

            if ($this->db->record_exists(self::PURCHASE, ['id' => (int)$current->purchaseid])) {
                $skippedids[] = $id;
                continue;
            }

            $this->db->delete_records(self::PAYMENT, ['id' => $id]);
            $deletedids[] = $id;
        }
        $transaction->allow_commit();

        return [
            'deleted' => count($deletedids),
            'skipped' => count($skippedids),
            'deletedids' => $deletedids,
            'skippedids' => $skippedids,
        ];
    }
}
