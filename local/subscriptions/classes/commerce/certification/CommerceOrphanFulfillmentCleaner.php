<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Inspects and removes fulfillment rows whose parent purchase no longer exists.
 *
 * The service is read-only unless execute() is called explicitly.
 */
final class CommerceOrphanFulfillmentCleaner {
    private const PURCHASE_TABLE = 'local_subscriptions_commerce_purchase';
    private const PURCHASE_ITEM_TABLE = 'local_subscriptions_commerce_purchase_item';
    private const FULFILLMENT_TABLE = 'local_subscriptions_commerce_fulfillment';
    private const GRANT_TABLE = 'local_subs_commerce_grant';
    private const DIGITAL_ACCESS_TABLE = 'local_subs_commerce_dig_access';
    private const FULFILLMENT_STATE_TABLE = 'local_subs_commerce_ful_state';

    public function __construct(private readonly moodle_database $db) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function inspect(int $limit = 0): array {
        $sql = 'SELECT f.*
                  FROM {' . self::FULFILLMENT_TABLE . '} f
             LEFT JOIN {' . self::PURCHASE_TABLE . '} p
                    ON p.id = f.purchaseid
                 WHERE p.id IS NULL
              ORDER BY f.id ASC';

        $records = $this->db->get_records_sql($sql, null, 0, $limit > 0 ? $limit : 0);
        $result = [];

        foreach ($records as $record) {
            $metadata = json_decode((string)$record->metadatajson, true);
            $metadata = is_array($metadata) ? $metadata : [];

            $grantreference = $this->first_non_empty_string([
                $metadata['grantreference'] ?? null,
                $metadata['grant_reference'] ?? null,
                $metadata['grant']['reference'] ?? null,
            ]);

            $dependencies = [
                'purchaseitems' => $this->db->count_records(
                    self::PURCHASE_ITEM_TABLE,
                    ['purchaseid' => (int)$record->purchaseid]
                ),
                'grants_by_idempotencykey' => $this->count_if_table_exists(
                    self::GRANT_TABLE,
                    ['idempotencykey' => (string)$record->idempotencykey]
                ),
                'digital_access_by_idempotencykey' => $this->count_if_table_exists(
                    self::DIGITAL_ACCESS_TABLE,
                    ['idempotencykey' => (string)$record->idempotencykey]
                ),
                'fulfillment_state_by_idempotencykey' => $this->count_if_table_exists(
                    self::FULFILLMENT_STATE_TABLE,
                    ['idempotencykey' => (string)$record->idempotencykey]
                ),
            ];

            if ($grantreference !== '') {
                $dependencies['grants_by_reference'] = $this->count_if_table_exists(
                    self::GRANT_TABLE,
                    ['grantreference' => $grantreference]
                );
                $dependencies['digital_access_by_reference'] = $this->count_if_table_exists(
                    self::DIGITAL_ACCESS_TABLE,
                    ['grantreference' => $grantreference]
                );
                $dependencies['fulfillment_state_by_reference'] = $this->count_if_table_exists(
                    self::FULFILLMENT_STATE_TABLE,
                    ['grantreference' => $grantreference]
                );
            }

            $dependencycount = array_sum($dependencies);

            $result[] = [
                'id' => (int)$record->id,
                'purchaseid' => (int)$record->purchaseid,
                'sequence' => (int)$record->sequence,
                'reference' => (string)$record->reference,
                'fulfillmentkey' => (string)$record->fulfillmentkey,
                'idempotencykey' => (string)$record->idempotencykey,
                'status' => (string)$record->status,
                'grantreference' => $grantreference,
                'dependencies' => $dependencies,
                'dependencycount' => $dependencycount,
                'safe_to_delete' => $dependencycount === 0,
            ];
        }

        return $result;
    }

    /**
     * Delete inspected orphan rows.
     *
     * Rows with detected dependent records are skipped unless $force is true.
     * Dependent records themselves are never deleted by this service.
     *
     * @return array{deleted:int, skipped:int, deletedids:array<int, int>, skippedids:array<int, int>}
     */
    public function execute(bool $force = false, int $limit = 0): array {
        $orphans = $this->inspect($limit);
        $deletedids = [];
        $skippedids = [];

        $transaction = $this->db->start_delegated_transaction();

        foreach ($orphans as $orphan) {
            if (!$orphan['safe_to_delete'] && !$force) {
                $skippedids[] = $orphan['id'];
                continue;
            }

            // Recheck immediately before deletion to remain safe under concurrency.
            if ($this->db->record_exists(self::PURCHASE_TABLE, ['id' => $orphan['purchaseid']])) {
                $skippedids[] = $orphan['id'];
                continue;
            }

            $this->db->delete_records(self::FULFILLMENT_TABLE, ['id' => $orphan['id']]);
            $deletedids[] = $orphan['id'];
        }

        $transaction->allow_commit();

        return [
            'deleted' => count($deletedids),
            'skipped' => count($skippedids),
            'deletedids' => $deletedids,
            'skippedids' => $skippedids,
        ];
    }

    /** @param array<string, int|string> $conditions */
    private function count_if_table_exists(string $table, array $conditions): int {
        if (!$this->db->get_manager()->table_exists($table)) {
            return 0;
        }

        return $this->db->count_records($table, $conditions);
    }

    /** @param array<int, mixed> $values */
    private function first_non_empty_string(array $values): string {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }
}
