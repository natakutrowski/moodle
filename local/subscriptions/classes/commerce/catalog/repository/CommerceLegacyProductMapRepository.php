<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Persists links between Native catalogue products and Legacy records.
 */
final class CommerceLegacyProductMapRepository {
    private const TABLE = 'local_subs_commerce_prod_map';

    public function __construct(private readonly \moodle_database $db) {
    }

    public function find_product_id(string $legacytable, int $legacyid): ?int {
        $record = $this->db->get_record(self::TABLE, [
            'legacytable' => trim($legacytable),
            'legacyid' => $legacyid,
        ], 'productid');

        return $record ? (int)$record->productid : null;
    }

    public function save(int $productid, string $legacyfamily, string $legacytable, int $legacyid): void {
        if ($productid <= 0 || $legacyid <= 0) {
            throw new \coding_exception('Commerce Legacy catalogue mappings require positive identifiers.');
        }

        $legacyfamily = strtolower(trim($legacyfamily));
        $legacytable = trim($legacytable);
        if ($legacyfamily === '' || $legacytable === '') {
            throw new \coding_exception('Commerce Legacy catalogue mappings require a family and table.');
        }

        $now = time();
        $existing = $this->db->get_record(self::TABLE, [
            'legacytable' => $legacytable,
            'legacyid' => $legacyid,
        ]);

        $data = (object)[
            'productid' => $productid,
            'legacyfamily' => $legacyfamily,
            'legacytable' => $legacytable,
            'legacyid' => $legacyid,
            'timemodified' => $now,
        ];

        if ($existing) {
            $data->id = $existing->id;
            $this->db->update_record(self::TABLE, $data);
            return;
        }

        $data->timecreated = $now;
        $this->db->insert_record(self::TABLE, $data);
    }


    public function find_by_product(int $productid, string $legacytable): ?\stdClass {
        $record = $this->db->get_record(self::TABLE, [
            'productid' => $productid,
            'legacytable' => trim($legacytable),
        ]);
        return $record ?: null;
    }

    public function find_legacy_link(string $legacytable, int $legacyid): ?\stdClass {
        $record = $this->db->get_record(self::TABLE, [
            'legacytable' => trim($legacytable),
            'legacyid' => $legacyid,
        ]);
        return $record ?: null;
    }

    public function transfer_product(int $productid, string $legacyfamily, string $legacytable, int $legacyid): void {
        $transaction = $this->db->start_delegated_transaction();
        try {
            $this->db->delete_records(self::TABLE, [
                'legacytable' => trim($legacytable),
                'legacyid' => $legacyid,
            ]);
            $this->link_product($productid, $legacyfamily, $legacytable, $legacyid);
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    public function link_product(int $productid, string $legacyfamily, string $legacytable, int $legacyid): void {
        $conflict = $this->db->get_record(self::TABLE, [
            'legacytable' => trim($legacytable),
            'legacyid' => $legacyid,
        ]);
        if ($conflict && (int)$conflict->productid !== $productid) {
            throw new \coding_exception('This Legacy record is already linked to another Native product.');
        }
        $current = $this->find_by_product($productid, $legacytable);
        if ($current && (int)$current->legacyid !== $legacyid) {
            $this->db->delete_records(self::TABLE, ['id' => (int)$current->id]);
        }
        $this->save($productid, $legacyfamily, $legacytable, $legacyid);
    }

    public function unlink_product(int $productid, string $legacytable): void {
        $this->db->delete_records(self::TABLE, [
            'productid' => $productid,
            'legacytable' => trim($legacytable),
        ]);
    }

    /** @return array<int, \stdClass> */
    public function find_by_family(string $legacyfamily): array {
        return array_values($this->db->get_records(self::TABLE, [
            'legacyfamily' => strtolower(trim($legacyfamily)),
        ], 'legacytable, legacyid'));
    }
}
