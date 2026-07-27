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