<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\persistence\sql;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/**
 * Native Commerce persistence repository.
 *
 * This repository exposes the complete SQL persistence boundary without
 * activating it in the Commerce runtime yet.
 */
final class CommercePurchaseSqlRepository {
    public function __construct(
        private readonly CommercePurchaseSqlWriter $writer,
        private readonly CommercePurchaseSqlReader $reader
    ) {
    }

    /**
     * Persist a complete Commerce purchase snapshot.
     *
     * @return int Internal SQL identifier of the aggregate root.
     */
    public function save(
        CommercePurchasePersistenceSnapshot $snapshot
    ): int {
        return $this->writer->write($snapshot);
    }

    public function find_by_uuid(
        string $purchaseuuid
    ): ?CommercePurchasePersistenceSnapshot {
        return $this->reader->find_by_uuid($purchaseuuid);
    }

    public function find_by_reference(
        string $reference
    ): ?CommercePurchasePersistenceSnapshot {
        return $this->reader->find_by_reference($reference);
    }

    public function find_by_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): ?CommercePurchasePersistenceSnapshot {
        return $this->reader->find_by_legacy_reference(
            $legacyfamily,
            $legacyid
        );
    }

    public function exists_for_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): bool {
        return $this->reader->exists_for_legacy_reference(
            $legacyfamily,
            $legacyid
        );
    }


    public function delete_by_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): bool {
        return $this->writer->delete_by_legacy_reference(
            $legacyfamily,
            $legacyid
        );
    }

    /** @return CommercePurchasePersistenceSnapshot[] */
    public function find_by_customer(int $userid, ?string $email = null): array {
        return $this->reader->find_by_customer($userid, $email);
    }

    public function find_by_legacy_payment_request(
        string $legacyfamily,
        int $legacyrequestid
    ): ?CommercePurchasePersistenceSnapshot {
        return $this->reader->find_by_legacy_payment_request(
            $legacyfamily,
            $legacyrequestid
        );
    }
}