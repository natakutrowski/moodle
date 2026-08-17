<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

final class CommerceProductRepository {
    private const TABLE = 'local_subs_commerce_product';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogHydrator $hydrator
    ) {
    }

    public function find_by_id(int $id): ?CommerceProduct {
        $record = $this->db->get_record(self::TABLE, ['id' => $id]);

        return $record ? $this->hydrator->product($record) : null;
    }

    public function find_by_sku(string $sku): ?CommerceProduct {
        $record = $this->db->get_record(
            self::TABLE,
            ['sku' => strtoupper(trim($sku))]
        );

        return $record ? $this->hydrator->product($record) : null;
    }

    /**
     * @return CommerceProduct[]
     */
    public function find_by_type(string $type): array {
        $records = $this->db->get_records(
            self::TABLE,
            ['type' => strtolower(trim($type))],
            'name ASC, id ASC'
        );

        return array_values(array_map($this->hydrator->product(...), $records));
    }

    /**
     * @return CommerceProduct[]
     */
    public function find_all(?string $type = null, ?string $status = null): array {
        $conditions = [];

        if ($type !== null && trim($type) !== '') {
            $conditions['type'] = strtolower(trim($type));
        }

        if ($status !== null && trim($status) !== '') {
            $conditions['status'] = strtolower(trim($status));
        }

        $records = $this->db->get_records(
            self::TABLE,
            $conditions,
            'name ASC, id ASC'
        );

        return array_values(array_map($this->hydrator->product(...), $records));
    }

    /** @return CommerceProduct[] */
    public function find_active(): array {
        $records = $this->db->get_records(
            self::TABLE,
            ['status' => 'active'],
            'name ASC'
        );

        return array_values(array_map($this->hydrator->product(...), $records));
    }

    public function save(CommerceProduct $product): CommerceProduct {
        $now = time();
        $data = (object) [
            'sku' => $product->get_sku(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'metadatajson' => CommerceCatalogHydrator::encode_json($product->get_metadata()),
            'availablefrom' => $product->get_available_from(),
            'availableuntil' => $product->get_available_until(),
            'timemodified' => $now,
        ];

        $existing = $product->get_id() !== null
            ? $this->db->get_record(self::TABLE, ['id' => $product->get_id()])
            : $this->db->get_record(self::TABLE, ['sku' => $product->get_sku()]);

        if ($existing) {
            $data->id = $existing->id;
            $this->db->update_record(self::TABLE, $data);
            $id = (int)$existing->id;
        } else {
            $data->timecreated = $now;
            $id = (int) $this->db->insert_record(self::TABLE, $data);
        }

        return $this->find_by_id($id)
            ?? throw new \RuntimeException('Unable to reload persisted Commerce product.');
    }
}
