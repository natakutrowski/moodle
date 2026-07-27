<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * SQL repository for the Native Commerce entitlement grant ledger.
 */
final class CommerceEntitlementGrantRepository {
    private const TABLE = 'local_subs_commerce_grant';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceEntitlementGrantRecordMapper $mapper
    ) {
    }

    public function find_by_idempotency_key(string $idempotencykey): ?\stdClass {
        $idempotencykey = trim($idempotencykey);

        if ($idempotencykey === '') {
            return null;
        }

        $record = $this->db->get_record(
            self::TABLE,
            ['idempotencykey' => $idempotencykey],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    public function find_by_reference(string $reference): ?\stdClass {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        $record = $this->db->get_record(
            self::TABLE,
            ['grantreference' => $reference],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    /**
     * Returns create, identical or conflict without writing anything.
     */
    public function classify(CommerceEntitlementGrant $grant): string {
        $existing = $this->find_by_idempotency_key($grant->get_idempotency_key());

        if ($existing === null) {
            return 'create';
        }

        return hash_equals(
            $this->mapper->record_payload_hash($existing),
            $this->mapper->payload_hash($grant)
        ) ? 'identical' : 'conflict';
    }

    public function insert(CommerceEntitlementGrant $grant, ?int $now = null): \stdClass {
        $record = $this->mapper->to_record($grant, 'planned', $now);
        $record->id = $this->db->insert_record(self::TABLE, $record);

        return $record;
    }


    public function update_status_by_idempotency_key(
        string $idempotencykey,
        string $status,
        ?int $now = null
    ): void {
        $record = $this->find_by_idempotency_key($idempotencykey);

        if ($record === null) {
            throw new \RuntimeException('The Native Commerce entitlement grant could not be found.');
        }

        $status = strtolower(trim($status));

        if (!preg_match('/^[a-z][a-z0-9_]{1,19}$/', $status)) {
            throw new \coding_exception('Invalid Native Commerce entitlement grant status.');
        }

        $this->db->update_record(self::TABLE, (object)[
            'id' => (int)$record->id,
            'status' => $status,
            'timemodified' => $now ?? time(),
        ]);
    }

    /**
     * @return \stdClass[]
     */
    public function find_by_purchase_reference(string $purchasereference): array {
        return array_values($this->db->get_records(
            self::TABLE,
            ['purchasereference' => trim($purchasereference)],
            'id ASC'
        ));
    }
}
