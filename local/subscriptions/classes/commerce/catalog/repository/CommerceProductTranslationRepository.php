<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

final class CommerceProductTranslationRepository {
    private const TABLE = 'local_subs_commerce_prod_tr';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogHydrator $hydrator,
        private readonly CommerceProductRepository $products
    ) {
    }

    public function find(string $sku, string $language): ?CommerceProductTranslation {
        $product = $this->products->find_by_sku($sku);

        if (!$product || $product->get_id() === null) {
            return null;
        }

        $record = $this->db->get_record(self::TABLE, [
            'productid' => $product->get_id(),
            'language' => strtolower(trim($language)),
        ]);

        return $record
            ? $this->hydrator->translation($record, $product->get_sku())
            : null;
    }

    /**
     * @return CommerceProductTranslation[]
     */
    public function find_by_product_sku(string $sku): array {
        $product = $this->products->find_by_sku($sku);

        if (!$product || $product->get_id() === null) {
            return [];
        }

        $records = $this->db->get_records(
            self::TABLE,
            ['productid' => $product->get_id()],
            'language ASC, id ASC'
        );

        return array_values(array_map(
            fn($record): CommerceProductTranslation => $this->hydrator->translation(
                $record,
                $product->get_sku()
            ),
            $records
        ));
    }

    public function save(CommerceProductTranslation $translation): CommerceProductTranslation {
        $product = $this->products->find_by_sku($translation->get_product_sku());

        if (!$product || $product->get_id() === null) {
            throw new \coding_exception(
                'Cannot persist a translation for an unknown Commerce product.'
            );
        }

        $key = [
            'productid' => $product->get_id(),
            'language' => $translation->get_language(),
        ];
        $existing = $this->db->get_record(self::TABLE, $key);
        $now = time();
        $data = (object) ($key + [
            'name' => $translation->get_name(),
            'shortdescription' => $translation->get_short_description(),
            'description' => $translation->get_description(),
            'metadatajson' => CommerceCatalogHydrator::encode_json($translation->get_metadata()),
            'timemodified' => $now,
        ]);

        if ($existing) {
            $data->id = $existing->id;
            $this->db->update_record(self::TABLE, $data);
            $id = (int) $existing->id;
        } else {
            $data->timecreated = $now;
            $id = (int) $this->db->insert_record(self::TABLE, $data);
        }

        $record = $this->db->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);

        return $this->hydrator->translation($record, $product->get_sku());
    }
}
