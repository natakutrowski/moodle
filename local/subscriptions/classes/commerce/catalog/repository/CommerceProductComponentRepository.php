<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

final class CommerceProductComponentRepository {
    private const TABLE = 'local_subs_commerce_prod_comp';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogHydrator $hydrator,
        private readonly CommerceProductRepository $products
    ) {
    }

    /** @return CommerceProductComponent[] */
    public function find_by_parent_sku(string $sku): array {
        $parent = $this->products->find_by_sku($sku);

        if (!$parent || $parent->get_id() === null) {
            return [];
        }

        $sql = 'SELECT c.*, child.sku AS childsku
                  FROM {' . self::TABLE . '} c
                  JOIN {local_subs_commerce_product} child
                    ON child.id = c.childproductid
                 WHERE c.parentproductid = :parentid
              ORDER BY c.sortorder, c.id';
        $records = $this->db->get_records_sql($sql, ['parentid' => $parent->get_id()]);

        return array_values(array_map(
            fn($record) => $this->hydrator->component(
                $record,
                $parent->get_sku(),
                $record->childsku
            ),
            $records
        ));
    }

    public function replace_for_parent(string $sku, array $components): void {
        $parent = $this->products->find_by_sku($sku);

        if (!$parent || $parent->get_id() === null) {
            throw new \coding_exception('Unknown parent Commerce product.');
        }

        $this->db->delete_records(self::TABLE, ['parentproductid' => $parent->get_id()]);
        $now = time();

        foreach ($components as $component) {
            if (
                !$component instanceof CommerceProductComponent
                || $component->get_parent_product_sku() !== $parent->get_sku()
            ) {
                throw new \coding_exception('Invalid Commerce bundle component collection.');
            }

            $child = $this->products->find_by_sku($component->get_child_product_sku());

            if (!$child || $child->get_id() === null) {
                throw new \coding_exception('Unknown child Commerce product.');
            }

            $this->db->insert_record(self::TABLE, (object) [
                'parentproductid' => $parent->get_id(),
                'childproductid' => $child->get_id(),
                'quantity' => $component->get_quantity(),
                'sortorder' => $component->get_sort_order(),
                'metadatajson' => CommerceCatalogHydrator::encode_json($component->get_metadata()),
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }
}
