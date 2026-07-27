<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

final class CommerceProductEntitlementRepository {
    private const TABLE = 'local_subs_commerce_prod_ent';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogHydrator $hydrator,
        private readonly CommerceProductRepository $products
    ) {
    }

    /** @return CommerceProductEntitlementDefinition[] */
    public function find_by_product_sku(string $sku): array {
        $product = $this->products->find_by_sku($sku);

        if (!$product || $product->get_id() === null) {
            return [];
        }

        $records = $this->db->get_records(
            self::TABLE,
            ['productid' => $product->get_id()],
            'sortorder, id'
        );

        return array_values(array_map(
            fn($record) => $this->hydrator->entitlement($record, $product->get_sku()),
            $records
        ));
    }

    public function replace_for_product(string $sku, array $definitions): void {
        $product = $this->products->find_by_sku($sku);

        if (!$product || $product->get_id() === null) {
            throw new \coding_exception('Unknown Commerce product.');
        }

        $this->db->delete_records(self::TABLE, ['productid' => $product->get_id()]);
        $now = time();

        foreach ($definitions as $definition) {
            if (
                !$definition instanceof CommerceProductEntitlementDefinition
                || $definition->get_product_sku() !== $product->get_sku()
            ) {
                throw new \coding_exception(
                    'Invalid Commerce entitlement definition collection.'
                );
            }

            $this->db->insert_record(self::TABLE, (object) [
                'productid' => $product->get_id(),
                'type' => $definition->get_type(),
                'resourcekey' => $definition->get_resource_key(),
                'durationseconds' => $definition->get_duration_seconds(),
                'quantity' => $definition->get_quantity(),
                'configurationjson' => CommerceCatalogHydrator::encode_json(
                    $definition->get_configuration()
                ),
                'sortorder' => $definition->get_sort_order(),
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }
}
