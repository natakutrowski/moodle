<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/** Optimised native purchase queries for read-side consumers. */
final class NativePurchaseReadRepository {
    public function __construct(private readonly moodle_database $database) {
    }

    public function find_record_by_legacy_reference(string $family, int $legacyid): ?\stdClass {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['legacyfamily' => trim($family), 'legacyid' => $legacyid],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $record;
    }

    public function find_record_by_reference(string $reference): ?\stdClass {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => trim($reference)],
            '*',
            IGNORE_MISSING
        );

        return $record === false ? null : $record;
    }

    /** @return \stdClass[] */
    public function find_records_by_customer(int $userid, ?string $email = null): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException('A positive customer identifier is required.');
        }

        $params = ['userid' => $userid];
        $where = 'userid = :userid';
        $email = trim((string)$email);

        if ($email !== '') {
            $where .= ' OR ' . $this->database->sql_equal('customeremail', ':email', false);
            $params['email'] = \core_text::strtolower($email);
        }

        return array_values($this->database->get_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            '(' . $where . ')',
            $params,
            'timecreated DESC, id DESC'
        ));
    }
}
