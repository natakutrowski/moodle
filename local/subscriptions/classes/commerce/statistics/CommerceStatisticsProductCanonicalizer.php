<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves immutable purchase-item snapshots to one canonical catalogue identity.
 *
 * Native items carry catalogue_sku in metadata. Legacy items carry legacy_table /
 * legacy_id and are resolved through local_subs_commerce_prod_map when available.
 * This prevents one logical product from appearing twice in analytics merely
 * because some sales were imported from Legacy and others came from Native.
 */
final class CommerceStatisticsProductCanonicalizer {
    /** @var array<string,array{key:string,reference:string,label:string,priority:int}> */
    private array $cache = [];

    public function __construct(private readonly \moodle_database $db) {}

    /**
     * @return array{key:string,reference:string,label:string,priority:int}
     */
    public function canonicalise(
        string $itemreference,
        string $label,
        string $metadatajson
    ): array {
        $itemreference = trim($itemreference);
        $label = trim($label);
        $metadata = json_decode($metadatajson, true);
        $metadata = is_array($metadata) ? $metadata : [];

        $cataloguesku = strtoupper(trim((string)($metadata['catalogue_sku'] ?? '')));
        if ($cataloguesku !== '') {
            $resolved = $this->native_product($cataloguesku, $label);
            $resolved['priority'] = 30;
            return $resolved;
        }

        $legacytable = trim((string)($metadata['legacy_table'] ?? ''));
        $legacyid = (int)($metadata['legacy_id'] ?? 0);
        if ($legacytable !== '' && $legacyid > 0) {
            $cachekey = 'legacy:' . $legacytable . ':' . $legacyid;
            if (isset($this->cache[$cachekey])) {
                return $this->cache[$cachekey];
            }

            $product = $this->db->get_record_sql(
                "SELECT p.sku, p.name
                   FROM {local_subs_commerce_prod_map} m
                   JOIN {local_subs_commerce_product} p ON p.id = m.productid
                  WHERE m.legacytable = :legacytable
                    AND m.legacyid = :legacyid",
                ['legacytable' => $legacytable, 'legacyid' => $legacyid],
                IGNORE_MISSING
            );
            if ($product) {
                $sku = strtoupper((string)$product->sku);
                $resolved = [
                    'key' => 'sku:' . $sku,
                    'reference' => (string)$product->sku,
                    'label' => trim((string)$product->name) !== '' ? (string)$product->name : $label,
                    'priority' => str_starts_with($sku, 'SUB.PLAN.') ? 10 : 20,
                ];
                $this->cache[$cachekey] = $resolved;
                return $resolved;
            }
        }

        $referencekey = strtoupper($itemreference);
        return [
            'key' => 'ref:' . $referencekey,
            'reference' => $itemreference,
            'label' => $label,
            'priority' => 0,
        ];
    }

    /**
     * @return array{key:string,reference:string,label:string,priority:int}
     */
    private function native_product(string $sku, string $fallbacklabel): array {
        $cachekey = 'sku:' . $sku;
        if (isset($this->cache[$cachekey])) {
            return $this->cache[$cachekey];
        }

        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            'sku,name',
            IGNORE_MISSING
        );

        $resolved = [
            'key' => $cachekey,
            'reference' => $product ? (string)$product->sku : $sku,
            'label' => $product && trim((string)$product->name) !== ''
                ? (string)$product->name
                : $fallbacklabel,
            'priority' => 20,
        ];
        $this->cache[$cachekey] = $resolved;
        return $resolved;
    }
}
