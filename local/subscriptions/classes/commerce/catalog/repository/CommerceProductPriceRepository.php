<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;

final class CommerceProductPriceRepository {
    private const TABLE = 'local_subs_commerce_prod_price';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogHydrator $hydrator,
        private readonly CommerceProductRepository $products
    ) {
    }

    /** @return CommerceProductPrice[] */
    public function find_by_product_sku(string $sku, bool $activeonly = false): array {
        $product = $this->products->find_by_sku($sku);
        if (!$product || $product->get_id() === null) {
            return [];
        }

        $conditions = ['productid' => $product->get_id()];
        if ($activeonly) {
            $conditions['active'] = 1;
        }

        $records = $this->db->get_records(self::TABLE, $conditions, 'currency, provider');
        return array_values(array_map(
            fn($record): CommerceProductPrice => $this->hydrator->price($record, $product->get_sku()),
            $records
        ));
    }


    public function find_by_id(int $id): ?CommerceProductPrice {
        $record = $this->db->get_record(self::TABLE, ['id' => $id]);
        if (!$record) {
            return null;
        }
        $product = $this->products->find_by_id((int)$record->productid);
        if (!$product) {
            return null;
        }
        return $this->hydrator->price($record, $product->get_sku());
    }

    public function update_by_id(CommerceProductPrice $price): CommerceProductPrice {
        if ($price->get_id() === null) {
            throw new \coding_exception('A price identifier is required for update.');
        }
        $product = $this->products->find_by_sku($price->get_product_sku());
        if (!$product || $product->get_id() === null) {
            throw new \coding_exception('Cannot update a price for an unknown Commerce product.');
        }
        $current = $this->db->get_record(self::TABLE, ['id' => $price->get_id(), 'productid' => $product->get_id()], '*', MUST_EXIST);
        $duplicate = $this->db->get_record_select(self::TABLE,
            'productid = :productid AND currency = :currency AND id <> :id',
            ['productid' => $product->get_id(), 'currency' => $price->get_currency(), 'id' => $price->get_id()]);
        if ($duplicate) {
            throw new \moodle_exception('commerce_price_currency_duplicate', 'local_subscriptions');
        }
        $current->currency = $price->get_currency();
        $current->amountminor = $price->get_amount_minor();
        $current->provider = self::provider($price->get_provider());
        $current->providerpriceid = $price->get_provider_price_id();
        $current->active = (int)$price->is_active();
        $current->metadatajson = CommerceCatalogHydrator::encode_json($price->get_metadata());
        $current->timemodified = time();
        $this->db->update_record(self::TABLE, $current);
        $saved = $this->find_by_id((int)$price->get_id());
        if ($saved === null) {
            throw new \coding_exception('Updated Commerce price could not be reloaded.');
        }
        return $saved;
    }

    public function delete_by_id(string $sku, int $id): void {
        $product = $this->products->find_by_sku($sku);
        if (!$product || $product->get_id() === null) {
            throw new \coding_exception('Unknown Commerce product.');
        }
        $this->db->delete_records(self::TABLE, ['id' => $id, 'productid' => $product->get_id()]);
    }

    public function currency_exists(string $sku, string $currency, ?int $excludeid = null): bool {
        $product = $this->products->find_by_sku($sku);
        if (!$product || $product->get_id() === null) {
            return false;
        }
        $params = ['productid' => $product->get_id(), 'currency' => strtoupper($currency)];
        $sql = 'productid = :productid AND currency = :currency';
        if ($excludeid !== null) {
            $sql .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }
        return $this->db->record_exists_select(self::TABLE, $sql, $params);
    }

    public function find_active(string $sku, string $currency, ?string $provider = null): ?CommerceProductPrice {
        $currency = strtoupper(trim($currency));
        $provider = self::provider($provider);
        $genericprice = null;

        foreach ($this->find_by_product_sku($sku, true) as $price) {
            if ($price->get_currency() !== $currency) {
                continue;
            }

            $priceprovider = self::provider($price->get_provider());
            if ($priceprovider === $provider) {
                return $price;
            }

            // A provider-neutral catalogue price is valid for any compatible provider.
            // Keep it as fallback, while always preferring an exact provider-specific price.
            if ($provider !== null && $priceprovider === null) {
                $genericprice = $price;
            }
        }

        return $genericprice;
    }

    public function save(CommerceProductPrice $price): CommerceProductPrice {
        $product = $this->products->find_by_sku($price->get_product_sku());
        if (!$product || $product->get_id() === null) {
            throw new \coding_exception('Cannot persist a price for an unknown Commerce product.');
        }

        $provider = self::provider($price->get_provider());
        $existing = $this->db->get_record(self::TABLE, [
            'productid' => $product->get_id(),
            'currency' => $price->get_currency(),
            'provider' => $provider,
        ]);
        $now = time();
        $data = (object)[
            'productid' => $product->get_id(),
            'currency' => $price->get_currency(),
            'amountminor' => $price->get_amount_minor(),
            'provider' => $provider,
            'providerpriceid' => $price->get_provider_price_id(),
            'active' => (int)$price->is_active(),
            'metadatajson' => CommerceCatalogHydrator::encode_json($price->get_metadata()),
            'timemodified' => $now,
        ];

        if ($existing) {
            $data->id = $existing->id;
            $this->db->update_record(self::TABLE, $data);
            $id = (int)$existing->id;
        } else {
            $data->timecreated = $now;
            $id = (int)$this->db->insert_record(self::TABLE, $data);
        }

        $record = $this->db->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return $this->hydrator->price($record, $product->get_sku());
    }

    private static function provider(?string $value): ?string {
        return $value === null || trim($value) === '' ? null : strtolower(trim($value));
    }
}