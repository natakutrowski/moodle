<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Read-only baseline auditor for Commerce storefront certification.
 *
 * This service never inserts, updates or deletes records.
 */
final class CommerceStorefrontBaselineAuditor {
    private const TABLES = [
        'products' => 'local_subs_commerce_product',
        'prices' => 'local_subs_commerce_prod_price',
        'translations' => 'local_subs_commerce_prod_tr',
        'components' => 'local_subs_commerce_prod_comp',
        'entitlements' => 'local_subs_commerce_prod_ent',
        'mappings' => 'local_subs_commerce_prod_map',
        'purchases' => 'local_subscriptions_commerce_purchase',
        'purchaseitems' => 'local_subscriptions_commerce_purchase_item',
        'fulfillments' => 'local_subscriptions_commerce_fulfillment',
        'grants' => 'local_subs_commerce_grant',
        'legacyplans' => 'subscription_plan',
        'legacyprices' => 'subscription_plan_price',
        'legacydigitalproducts' => 'subscription_digital_product',
        'legacydigitalpayments' => 'subscription_digital_payment_request',
    ];

    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(): CommerceStorefrontBaselineReport {
        $report = new CommerceStorefrontBaselineReport();
        $manager = $this->db->get_manager();
        $available = [];

        foreach (self::TABLES as $key => $tablename) {
            $exists = $manager->table_exists($tablename);
            $available[$key] = $exists;
            $report->add_check('table_' . $key, $exists);
            if (!$exists) {
                $report->add_issue(
                    'blocking',
                    'missing_table_' . $key,
                    'Required Commerce certification table is missing.',
                    ['table' => $tablename]
                );
            }
        }

        $report->add_inventory('tables', $available);
        if (!$available['products']) {
            return $report;
        }

        $this->audit_products($report, $available);
        $this->audit_prices($report, $available);
        $this->audit_relations($report, $available);
        $this->audit_purchases($report, $available);
        $this->audit_legacy($report, $available);
        $this->audit_merchandising($report);

        return $report;
    }

    /** @param array<string, bool> $available */
    private function audit_products(CommerceStorefrontBaselineReport $report, array $available): void {
        $total = $this->db->count_records(self::TABLES['products']);
        $statuscounts = $this->count_by_field(self::TABLES['products'], 'status');
        $typecounts = $this->count_by_field(self::TABLES['products'], 'type');

        $report->add_inventory('native_products_total', $total);
        $report->add_inventory('native_products_by_status', $statuscounts);
        $report->add_inventory('native_products_by_type', $typecounts);

        $duplicateSkus = $this->db->get_records_sql(
            'SELECT sku, COUNT(1) AS recordcount
               FROM {' . self::TABLES['products'] . '}
           GROUP BY sku
             HAVING COUNT(1) > 1'
        );
        $report->add_inventory('duplicate_skus', count($duplicateSkus));
        foreach ($duplicateSkus as $row) {
            $report->add_issue('blocking', 'duplicate_product_sku', 'Several Native products use the same SKU.', [
                'sku' => (string)$row->sku,
                'count' => (int)$row->recordcount,
            ]);
        }

        $invalidwindows = $this->db->count_records_select(
            self::TABLES['products'],
            'availablefrom IS NOT NULL AND availableuntil IS NOT NULL AND availableuntil < availablefrom'
        );
        $report->add_inventory('invalid_availability_windows', $invalidwindows);
        if ($invalidwindows > 0) {
            $report->add_issue(
                'important',
                'invalid_availability_window',
                'Products have an availability end date before their start date.',
                ['count' => $invalidwindows]
            );
        }

        if ($available['translations']) {
            $orphantranslations = $this->count_orphans(
                self::TABLES['translations'],
                'productid',
                self::TABLES['products'],
                'id'
            );
            $report->add_inventory('orphan_translations', $orphantranslations);
            if ($orphantranslations > 0) {
                $report->add_issue('important', 'orphan_product_translation', 'Translations reference missing products.', [
                    'count' => $orphantranslations,
                ]);
            }
        }
    }

    /** @param array<string, bool> $available */
    private function audit_prices(CommerceStorefrontBaselineReport $report, array $available): void {
        if (!$available['prices']) {
            return;
        }

        $report->add_inventory('native_prices_total', $this->db->count_records(self::TABLES['prices']));
        $report->add_inventory('native_prices_active', $this->db->count_records(self::TABLES['prices'], ['active' => 1]));
        $report->add_inventory('native_prices_inactive', $this->db->count_records(self::TABLES['prices'], ['active' => 0]));
        $report->add_inventory('native_prices_by_currency', $this->count_by_field(self::TABLES['prices'], 'currency'));

        $orphanprices = $this->count_orphans(
            self::TABLES['prices'],
            'productid',
            self::TABLES['products'],
            'id'
        );
        $report->add_inventory('orphan_prices', $orphanprices);
        if ($orphanprices > 0) {
            $report->add_issue('blocking', 'orphan_product_price', 'Prices reference missing Native products.', [
                'count' => $orphanprices,
            ]);
        }

        $negativeprices = $this->db->count_records_select(self::TABLES['prices'], 'amountminor < 0');
        $report->add_inventory('negative_prices', $negativeprices);
        if ($negativeprices > 0) {
            $report->add_issue('blocking', 'negative_native_price', 'Native prices contain negative amounts.', [
                'count' => $negativeprices,
            ]);
        }

        $duplicates = $this->records_from_sql(
            'SELECT productid, currency, provider, COUNT(1) AS recordcount
               FROM {' . self::TABLES['prices'] . '}
              WHERE active = 1
           GROUP BY productid, currency, provider
             HAVING COUNT(1) > 1'
        );
        $report->add_inventory('duplicate_active_price_groups', count($duplicates));
        foreach ($duplicates as $row) {
            $report->add_issue('blocking', 'duplicate_active_price', 'A product has several active prices for one currency/provider.', [
                'productid' => (int)$row->productid,
                'currency' => (string)$row->currency,
                'provider' => (string)$row->provider,
                'count' => (int)$row->recordcount,
            ]);
        }

        $withoutprice = $this->db->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::TABLES['products'] . '} p
              WHERE p.status = :status
                AND NOT EXISTS (
                    SELECT 1
                      FROM {' . self::TABLES['prices'] . '} pr
                     WHERE pr.productid = p.id
                       AND pr.active = 1
                )',
            ['status' => 'active']
        );
        $report->add_inventory('active_products_without_active_price', $withoutprice);
        if ($withoutprice > 0) {
            $report->add_issue('important', 'active_product_without_price', 'Active products have no active Native price.', [
                'count' => $withoutprice,
            ]);
        }
    }

    /** @param array<string, bool> $available */
    private function audit_relations(CommerceStorefrontBaselineReport $report, array $available): void {
        foreach ([
            'components' => ['parentproductid', 'childproductid'],
            'entitlements' => ['productid'],
            'mappings' => ['productid'],
        ] as $key => $fields) {
            if (!$available[$key]) {
                continue;
            }

            $report->add_inventory('native_' . $key . '_total', $this->db->count_records(self::TABLES[$key]));
            foreach ($fields as $field) {
                $count = $this->count_orphans(
                    self::TABLES[$key],
                    $field,
                    self::TABLES['products'],
                    'id'
                );
                $inventorykey = 'orphan_' . $key . '_' . $field;
                $report->add_inventory($inventorykey, $count);
                if ($count > 0) {
                    $report->add_issue('blocking', $inventorykey, 'A Native catalogue relation references a missing product.', [
                        'table' => self::TABLES[$key],
                        'field' => $field,
                        'count' => $count,
                    ]);
                }
            }
        }

        if ($available['mappings']) {
            $duplicates = $this->records_from_sql(
                'SELECT legacyfamily, legacytable, legacyid, COUNT(1) AS recordcount
                   FROM {' . self::TABLES['mappings'] . '}
               GROUP BY legacyfamily, legacytable, legacyid
                 HAVING COUNT(1) > 1'
            );
            $report->add_inventory('duplicate_canonical_legacy_mappings', count($duplicates));
            foreach ($duplicates as $row) {
                $report->add_issue(
                    'blocking',
                    'duplicate_canonical_legacy_mapping',
                    'A Legacy record is mapped canonically to several Native products.',
                    [
                        'legacyfamily' => (string)$row->legacyfamily,
                        'legacytable' => (string)$row->legacytable,
                        'legacyid' => (int)$row->legacyid,
                        'count' => (int)$row->recordcount,
                    ]
                );
            }
        }
    }

    /** @param array<string, bool> $available */
    private function audit_purchases(CommerceStorefrontBaselineReport $report, array $available): void {
        if (!$available['purchases']) {
            return;
        }

        $report->add_inventory('native_purchases_total', $this->db->count_records(self::TABLES['purchases']));
        $report->add_inventory('native_purchases_by_status', $this->count_by_field(self::TABLES['purchases'], 'status'));
        $report->add_inventory('native_purchases_by_currency', $this->count_by_field(self::TABLES['purchases'], 'currency'));

        if ($available['purchaseitems']) {
            $report->add_inventory('native_purchase_items_total', $this->db->count_records(self::TABLES['purchaseitems']));
            $orphans = $this->count_orphans(
                self::TABLES['purchaseitems'],
                'purchaseid',
                self::TABLES['purchases'],
                'id'
            );
            $report->add_inventory('orphan_purchase_items', $orphans);
            if ($orphans > 0) {
                $report->add_issue('blocking', 'orphan_purchase_item', 'Purchase items reference missing purchases.', [
                    'count' => $orphans,
                ]);
            }
        }

        if ($available['fulfillments']) {
            $report->add_inventory('native_fulfillments_total', $this->db->count_records(self::TABLES['fulfillments']));
            $report->add_inventory(
                'native_fulfillments_by_status',
                $this->count_by_field(self::TABLES['fulfillments'], 'status')
            );
            $orphans = $this->count_orphans(
                self::TABLES['fulfillments'],
                'purchaseid',
                self::TABLES['purchases'],
                'id'
            );
            $report->add_inventory('orphan_fulfillments', $orphans);
            if ($orphans > 0) {
                $report->add_issue('blocking', 'orphan_fulfillment', 'Fulfillments reference missing purchases.', [
                    'count' => $orphans,
                ]);
            }
        }

        if ($available['grants']) {
            $report->add_inventory('native_grants_total', $this->db->count_records(self::TABLES['grants']));
            $report->add_inventory('native_grants_by_status', $this->count_by_field(self::TABLES['grants'], 'status'));
        }
    }

    /** @param array<string, bool> $available */
    private function audit_legacy(CommerceStorefrontBaselineReport $report, array $available): void {
        if ($available['legacyplans']) {
            $report->add_inventory('legacy_plans_total', $this->db->count_records(self::TABLES['legacyplans']));
            $report->add_inventory('legacy_plans_active', $this->db->count_records(self::TABLES['legacyplans'], ['is_active' => 1]));
        }
        if ($available['legacyprices']) {
            $report->add_inventory('legacy_plan_prices_total', $this->db->count_records(self::TABLES['legacyprices']));
        }
        if ($available['legacydigitalproducts']) {
            $report->add_inventory(
                'legacy_digital_products_total',
                $this->db->count_records(self::TABLES['legacydigitalproducts'])
            );
            $report->add_inventory(
                'legacy_digital_products_active',
                $this->db->count_records(self::TABLES['legacydigitalproducts'], ['enabled' => 1])
            );
        }
        if ($available['legacydigitalpayments']) {
            $report->add_inventory(
                'legacy_digital_payments_total',
                $this->db->count_records(self::TABLES['legacydigitalpayments'])
            );
            $report->add_inventory(
                'legacy_digital_payments_by_status',
                $this->count_by_field(self::TABLES['legacydigitalpayments'], 'status')
            );
        }
    }

    private function audit_merchandising(CommerceStorefrontBaselineReport $report): void {
        $records = $this->db->get_records(self::TABLES['products'], null, 'id ASC', 'id, metadatajson');
        $featured = 0;
        $withbadges = 0;
        $withpromotions = 0;
        $invalidjson = 0;
        $expiredpromotions = 0;
        $futurepromotions = 0;
        $now = time();

        foreach ($records as $record) {
            $metadata = json_decode((string)$record->metadatajson, true);
            if (!is_array($metadata)) {
                $invalidjson++;
                continue;
            }

            $storefront = $metadata['storefront'] ?? [];
            if (is_string($storefront)) {
                $decodedstorefront = json_decode($storefront, true);
                $storefront = is_array($decodedstorefront) ? $decodedstorefront : [];
            }
            if (!is_array($storefront)) {
                continue;
            }
            $merchandising = $storefront['merchandising'] ?? [];
            if (!is_array($merchandising)) {
                continue;
            }

            if (!empty($merchandising['featured'])) {
                $featured++;
            }
            if (!empty($merchandising['badges']) && is_array($merchandising['badges'])) {
                $withbadges++;
            }

            $promotions = $merchandising['promotions'] ?? [];
            if (!is_array($promotions) || $promotions === []) {
                continue;
            }
            $withpromotions++;
            foreach ($promotions as $promotion) {
                if (!is_array($promotion)) {
                    continue;
                }
                $start = isset($promotion['start']) ? (int)$promotion['start'] : null;
                $end = isset($promotion['end']) ? (int)$promotion['end'] : null;
                if ($end !== null && $end > 0 && $end < $now) {
                    $expiredpromotions++;
                } else if ($start !== null && $start > $now) {
                    $futurepromotions++;
                }
            }
        }

        $report->add_inventory('products_featured', $featured);
        $report->add_inventory('products_with_badges', $withbadges);
        $report->add_inventory('products_with_promotions', $withpromotions);
        $report->add_inventory('expired_promotions_configured', $expiredpromotions);
        $report->add_inventory('future_promotions_configured', $futurepromotions);
        $report->add_inventory('products_with_invalid_metadata_json', $invalidjson);

        if ($invalidjson > 0) {
            $report->add_issue('important', 'invalid_product_metadata_json', 'Product metadata contains invalid JSON.', [
                'count' => $invalidjson,
            ]);
        }
    }

    /** @return array<int, object> */
    private function records_from_sql(string $sql, array $params = []): array {
        $records = [];
        $recordset = $this->db->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $records[] = $record;
        }
        $recordset->close();
        return $records;
    }

    /** @return array<string, int> */
    private function count_by_field(string $table, string $field): array {
        $records = $this->db->get_records_sql(
            'SELECT ' . $field . ', COUNT(1) AS recordcount
               FROM {' . $table . '}
           GROUP BY ' . $field . '
           ORDER BY ' . $field . ' ASC'
        );
        $result = [];
        foreach ($records as $record) {
            $key = (string)($record->{$field} ?? '');
            $result[$key] = (int)$record->recordcount;
        }
        return $result;
    }

    private function count_orphans(
        string $childtable,
        string $childfield,
        string $parenttable,
        string $parentfield
    ): int {
        return (int)$this->db->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . $childtable . '} child
          LEFT JOIN {' . $parenttable . '} parent
                 ON parent.' . $parentfield . ' = child.' . $childfield . '
              WHERE parent.' . $parentfield . ' IS NULL'
        );
    }
}
