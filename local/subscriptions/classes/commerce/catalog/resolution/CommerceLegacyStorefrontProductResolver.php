<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\resolution;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;

/**
 * Resolves a Legacy catalogue record to its canonical Native storefront product.
 *
 * The explicit mapping table is authoritative. Missing mappings can be recovered
 * from the Native product metadata or the deterministic SKU used by the importer.
 */
final class CommerceLegacyStorefrontProductResolver {
    private const LEGACY_TABLES = [
        'subscription_plan' => 'subscription',
        'subscription_digital_product' => 'digital',
    ];

    private CommerceLegacyProductMapRepository $maps;

    public function __construct(private readonly \moodle_database $db) {
        $this->maps = new CommerceLegacyProductMapRepository($db);
    }

    public function resolve_subscription_plan(int $planid, bool $repair = false): ?\stdClass {
        return $this->resolve('subscription_plan', $planid, $repair);
    }

    public function resolve_digital_product(int $productid, bool $repair = false): ?\stdClass {
        return $this->resolve('subscription_digital_product', $productid, $repair);
    }

    public function resolve(string $legacytable, int $legacyid, bool $repair = false): ?\stdClass {
        $legacytable = trim($legacytable);
        if (!isset(self::LEGACY_TABLES[$legacytable]) || $legacyid <= 0) {
            return null;
        }

        $mapped = $this->db->get_record_sql(
            "SELECT p.*
               FROM {local_subs_commerce_prod_map} m
               JOIN {local_subs_commerce_product} p ON p.id = m.productid
              WHERE m.legacytable = :legacytable
                AND m.legacyid = :legacyid",
            ['legacytable' => $legacytable, 'legacyid' => $legacyid],
            IGNORE_MISSING
        );
        if ($mapped) {
            return $mapped;
        }

        $recovered = $this->find_by_metadata($legacytable, $legacyid)
            ?? $this->find_by_deterministic_sku($legacytable, $legacyid);
        if (!$recovered) {
            return null;
        }

        if ($repair) {
            $this->maps->save(
                (int)$recovered->id,
                self::LEGACY_TABLES[$legacytable],
                $legacytable,
                $legacyid
            );
        }

        return $recovered;
    }

    public function storefront_url(string $legacytable, int $legacyid, bool $repair = false): ?\moodle_url {
        $product = $this->resolve($legacytable, $legacyid, $repair);
        $sku = $product ? trim((string)$product->sku) : '';
        return $sku === '' ? null : new \moodle_url('/local/subscriptions/storefront_product.php', ['sku' => $sku]);
    }

    private function find_by_metadata(string $legacytable, int $legacyid): ?\stdClass {
        $family = self::LEGACY_TABLES[$legacytable];
        foreach ($this->db->get_records('local_subs_commerce_product', null, 'id ASC') as $product) {
            $metadata = json_decode((string)($product->metadatajson ?? ''), true);
            if (!is_array($metadata)) {
                continue;
            }
            if (strtolower(trim((string)($metadata['legacyfamily'] ?? ''))) !== $family) {
                continue;
            }
            if ((int)($metadata['legacyid'] ?? 0) === $legacyid) {
                return $product;
            }
        }
        return null;
    }

    private function find_by_deterministic_sku(string $legacytable, int $legacyid): ?\stdClass {
        if ($legacytable === 'subscription_plan') {
            $sku = 'SUB.PLAN.' . $legacyid;
        } else {
            $legacy = $this->db->get_record(
                'subscription_digital_product',
                ['id' => $legacyid],
                'id,slug',
                IGNORE_MISSING
            );
            if (!$legacy) {
                return null;
            }
            $sku = 'DIGITAL.' . strtoupper((string)preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$legacy->slug));
        }

        return $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            '*',
            IGNORE_MISSING
        ) ?: null;
    }
}
