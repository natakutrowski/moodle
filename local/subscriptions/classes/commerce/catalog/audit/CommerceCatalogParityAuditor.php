<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

/**
 * Compares imported Native catalogue records with their Legacy sources.
 */
final class CommerceCatalogParityAuditor {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations,
        private readonly CommerceProductEntitlementRepository $entitlements
    ) {
    }

    /** @return array{checked:int,equal:int,different:int,missing:int,details:array} */
    public function audit(): array {
        $report = ['checked' => 0, 'equal' => 0, 'different' => 0, 'missing' => 0, 'details' => []];
        foreach ($this->db->get_records('local_subs_commerce_prod_map', null, 'legacytable, legacyid') as $map) {
            $report['checked']++;
            $native = $this->products->find_by_id((int)$map->productid);
            if ($native === null) {
                $report['missing']++;
                $report['details'][] = ['source' => $map->legacytable . '#' . $map->legacyid, 'status' => 'missing_native'];
                continue;
            }

            $differences = match ((string)$map->legacytable) {
                'subscription_plan' => $this->compare_subscription((int)$map->legacyid, $native),
                'subscription_digital_product' => $this->compare_digital((int)$map->legacyid, $native),
                default => ['unsupported_legacy_table'],
            };

            if ($differences === []) {
                $report['equal']++;
            } else {
                $report['different']++;
                $report['details'][] = [
                    'source' => $map->legacytable . '#' . $map->legacyid,
                    'sku' => $native->get_sku(),
                    'status' => 'different',
                    'differences' => $differences,
                ];
            }
        }
        return $report;
    }

    private function compare_subscription(int $legacyid, object $native): array {
        $legacy = $this->db->get_record('subscription_plan', ['id' => $legacyid]);
        if (!$legacy) {
            return ['missing_legacy'];
        }
        $differences = [];
        if (trim((string)$legacy->name) !== $native->get_name()) {
            $differences[] = 'name';
        }
        if ((bool)$legacy->is_active !== $native->is_active()) {
            $differences[] = 'status';
        }
        if (count($this->db->get_records('subscription_plan_price', ['planid' => $legacyid])) !== count($this->prices->find_by_product_sku($native->get_sku()))) {
            $differences[] = 'prices_count';
        }
        if (count($this->db->get_records('subscription_plan_translation', ['planid' => $legacyid])) !== $this->count_native_translations($native->get_sku())) {
            $differences[] = 'translations_count';
        }
        $expectedentitlements = $this->expected_subscription_entitlement_count($legacy);
        if ($expectedentitlements !== count($this->entitlements->find_by_product_sku($native->get_sku()))) {
            $differences[] = 'entitlements_count';
        }
        return $differences;
    }

    private function expected_subscription_entitlement_count(object $legacy): int {
        $explicit = $this->db->count_records('subscription_plan_entitlement', ['planid' => (int)$legacy->id]);
        if ($explicit > 0) {
            return $explicit;
        }

        $scopeid = (int)($legacy->accessscopeid ?? 0);
        if ($scopeid <= 0) {
            return 0;
        }
        $raw = $this->db->get_field('subscription_access_scope', 'course_ids', ['id' => $scopeid], IGNORE_MISSING);
        if ($raw === false || trim((string)$raw) === '') {
            return 0;
        }
        $ids = preg_split('/[,;\s]+/', trim((string)$raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return count(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
    }

    private function compare_digital(int $legacyid, object $native): array {
        $legacy = $this->db->get_record('subscription_digital_product', ['id' => $legacyid]);
        if (!$legacy) {
            return ['missing_legacy'];
        }
        $differences = [];
        if (trim((string)$legacy->name) !== $native->get_name()) {
            $differences[] = 'name';
        }
        if ((bool)$legacy->enabled !== $native->is_active()) {
            $differences[] = 'status';
        }
        if (count($this->prices->find_by_product_sku($native->get_sku())) !== 2) {
            $differences[] = 'prices_count';
        }
        if (count($this->db->get_records('subscription_digital_product_lang', ['productid' => $legacyid])) !== $this->count_native_translations($native->get_sku())) {
            $differences[] = 'translations_count';
        }
        return $differences;
    }

    private function count_native_translations(string $sku): int {
        $product = $this->products->find_by_sku($sku);
        return $product && $product->get_id() ? $this->db->count_records('local_subs_commerce_prod_tr', ['productid' => $product->get_id()]) : 0;
    }
}
