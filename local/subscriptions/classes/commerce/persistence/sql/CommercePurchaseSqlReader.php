<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\persistence\sql;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use moodle_database;

final class CommercePurchaseSqlReader {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePurchaseSqlHydrator $hydrator
    ) {
    }

    public function find_by_uuid(
        string $purchaseuuid
    ): ?CommercePurchasePersistenceSnapshot {
        $purchaseuuid = trim($purchaseuuid);

        if ($purchaseuuid === '') {
            throw new \coding_exception(
                'A Commerce purchase UUID is required.'
            );
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['purchaseuuid' => $purchaseuuid],
            '*',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return null;
        }

        return $this->hydrate_purchase($purchase);
    }

    public function find_by_reference(
        string $reference
    ): ?CommercePurchasePersistenceSnapshot {
        $reference = trim($reference);

        if ($reference === '') {
            throw new \coding_exception(
                'A Commerce purchase reference is required.'
            );
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $reference],
            '*',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return null;
        }

        return $this->hydrate_purchase($purchase);
    }    

    public function find_by_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): ?CommercePurchasePersistenceSnapshot {
        $legacyfamily = trim($legacyfamily);

        if ($legacyfamily === '') {
            throw new \coding_exception(
                'A Commerce Legacy family is required.'
            );
        }

        if ($legacyid <= 0) {
            throw new \coding_exception(
                'A Commerce Legacy identifier must be positive.'
            );
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'legacyfamily' => $legacyfamily,
                'legacyid' => $legacyid,
            ],
            '*',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return null;
        }

        return $this->hydrate_purchase($purchase);
    }    

    public function exists_for_legacy_reference(
        string $legacyfamily,
        int $legacyid
    ): bool {
        $legacyfamily = trim($legacyfamily);

        if ($legacyfamily === '' || $legacyid <= 0) {
            return false;
        }

        return $this->database->record_exists(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'legacyfamily' => $legacyfamily,
                'legacyid' => $legacyid,
            ]
        );
    }

    public function find_by_legacy_payment_request(
        string $legacyfamily,
        int $legacyrequestid
    ): ?CommercePurchasePersistenceSnapshot {
        $legacyfamily = trim($legacyfamily);

        if ($legacyfamily === '') {
            throw new \coding_exception(
                'A Commerce Legacy family is required.'
            );
        }

        if ($legacyrequestid <= 0) {
            throw new \coding_exception(
                'A Legacy payment request identifier must be positive.'
            );
        }

        $sql = "
            SELECT p.*
            FROM {" . CommercePersistenceSchema::TABLE_PURCHASE . "} p
            JOIN {" . CommercePersistenceSchema::TABLE_PAYMENT . "} cp
                ON cp.purchaseid = p.id
            WHERE p.legacyfamily = :legacyfamily
            AND cp.legacyrequestid = :legacyrequestid
        ORDER BY p.id ASC
        ";

        $purchases = $this->database->get_records_sql(
            $sql,
            [
                'legacyfamily' => $legacyfamily,
                'legacyrequestid' => $legacyrequestid,
            ],
            0,
            2
        );

        if ($purchases === []) {
            return null;
        }

        if (count($purchases) > 1) {
            throw new \coding_exception(
                'Several Commerce purchases match the same Legacy '
                    . 'payment request reference.'
            );
        }

        $purchase = reset($purchases);

        return $this->hydrate_purchase($purchase);
    }    


    /**
     * Return every native purchase linked to a CRM customer.
     *
     * @return CommercePurchasePersistenceSnapshot[]
     */
    public function find_by_customer(int $userid, ?string $email = null): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException('A positive Commerce customer identifier is required.');
        }

        $params = ['userid' => $userid];
        $where = 'userid = :userid';
        $email = $email !== null ? trim(\core_text::strtolower($email)) : '';
        if ($email !== '') {
            $where .= ' OR ' . $this->database->sql_equal('customeremail', ':customeremail', false);
            $params['customeremail'] = $email;
        }

        $records = $this->database->get_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            '(' . $where . ')',
            $params,
            'timecreated DESC, id DESC'
        );

        $snapshots = [];
        foreach ($records as $record) {
            $snapshots[] = $this->hydrate_purchase($record);
        }
        return $snapshots;
    }

    private function hydrate_purchase(
        \stdClass $purchase
    ): CommercePurchasePersistenceSnapshot {
        $purchaseid = (int) $purchase->id;

        $items = $this->database->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => $purchaseid],
            'position ASC, id ASC'
        );

        $payments = $this->database->get_records(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => $purchaseid],
            'sequence ASC, id ASC'
        );

        $fulfillments = $this->database->get_records(
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            ['purchaseid' => $purchaseid],
            'sequence ASC, id ASC'
        );

        return $this->hydrator->hydrate(
            $purchase,
            array_values($items),
            array_values($payments),
            array_values($fulfillments)
        );
    }    

}