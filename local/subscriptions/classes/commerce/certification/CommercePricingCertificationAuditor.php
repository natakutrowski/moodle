<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Read-only certification audit for Native prices and merchandising promotions.
 */
final class CommercePricingCertificationAuditor {
    private const PRODUCT_TABLE = 'local_subs_commerce_product';
    private const PRICE_TABLE = 'local_subs_commerce_prod_price';

    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F7B');
        $manager = $this->db->get_manager();

        foreach ([self::PRODUCT_TABLE, self::PRICE_TABLE] as $table) {
            if (!$manager->table_exists($table)) {
                $report->add_issue('blocking', 'missing_table', 'Required pricing table is missing.', ['table' => $table]);
                return $report;
            }
        }

        $report->add_inventory('prices_total', $this->db->count_records(self::PRICE_TABLE));
        $report->add_inventory('prices_active', $this->db->count_records(self::PRICE_TABLE, ['active' => 1]));

        $negative = $this->db->count_records_select(self::PRICE_TABLE, 'amountminor < 0');
        $report->add_inventory('negative_prices', $negative);
        if ($negative > 0) {
            $report->add_issue('blocking', 'negative_price', 'Negative Native prices are forbidden.', ['count' => $negative]);
        }

        $badcurrency = $this->db->count_records_select(
            self::PRICE_TABLE,
            "currency IS NULL OR currency = '' OR " . $this->db->sql_length('currency') . ' <> 3'
        );
        $report->add_inventory('invalid_currency_codes', $badcurrency);
        if ($badcurrency > 0) {
            $report->add_issue('blocking', 'invalid_currency', 'Prices contain invalid ISO currency codes.', ['count' => $badcurrency]);
        }

        $duplicates = $this->db->get_records_sql(
            'SELECT productid, currency, provider, COUNT(1) AS recordcount
               FROM {' . self::PRICE_TABLE . '}
              WHERE active = 1
           GROUP BY productid, currency, provider
             HAVING COUNT(1) > 1'
        );
        $report->add_inventory('duplicate_active_price_groups', count($duplicates));
        foreach ($duplicates as $row) {
            $report->add_issue('blocking', 'duplicate_active_price', 'Several active prices share the same product, currency and provider.', [
                'productid' => (int)$row->productid,
                'currency' => (string)$row->currency,
                'provider' => (string)$row->provider,
                'count' => (int)$row->recordcount,
            ]);
        }

        $withoutprice = $this->db->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::PRODUCT_TABLE . '} p
              WHERE p.status = :status
                AND NOT EXISTS (
                    SELECT 1 FROM {' . self::PRICE_TABLE . '} pr
                     WHERE pr.productid = p.id AND pr.active = 1
                )',
            ['status' => 'active']
        );
        $report->add_inventory('active_products_without_price', $withoutprice);
        if ($withoutprice > 0) {
            $report->add_issue('blocking', 'active_product_without_price', 'Active products must expose at least one active price.', ['count' => $withoutprice]);
        }

        $this->audit_promotions($report);
        return $report;
    }

    private function audit_promotions(CommerceCertificationReport $report): void {
        $rows = $this->db->get_records_select(self::PRODUCT_TABLE, "metadatajson IS NOT NULL AND metadatajson <> ''", null, '', 'id,sku,metadatajson');
        $configured = 0;
        $invalidjson = 0;
        $invalidamount = 0;
        $invalidwindow = 0;

        foreach ($rows as $row) {
            $metadata = json_decode((string)$row->metadatajson, true);
            if (!is_array($metadata)) {
                $invalidjson++;
                continue;
            }
            $promotion = $metadata['promotion'] ?? null;
            if (!is_array($promotion)) {
                continue;
            }
            $configured++;
            $catalogue = isset($promotion['catalogue_minor']) ? (int)$promotion['catalogue_minor'] : null;
            $promotional = isset($promotion['promotional_minor']) ? (int)$promotion['promotional_minor'] : null;
            if ($catalogue !== null && $promotional !== null && ($promotional < 0 || $catalogue <= 0 || $promotional >= $catalogue)) {
                $invalidamount++;
                $report->add_issue('blocking', 'invalid_promotion_amount', 'Promotion amount must be lower than catalogue amount.', ['productid' => (int)$row->id, 'sku' => (string)$row->sku]);
            }
            $starts = isset($promotion['starts_at']) ? (int)$promotion['starts_at'] : null;
            $ends = isset($promotion['ends_at']) ? (int)$promotion['ends_at'] : null;
            if ($starts !== null && $ends !== null && $ends < $starts) {
                $invalidwindow++;
                $report->add_issue('blocking', 'invalid_promotion_window', 'Promotion end date is earlier than its start date.', ['productid' => (int)$row->id, 'sku' => (string)$row->sku]);
            }
        }

        $report->add_inventory('promotions_configured', $configured);
        $report->add_inventory('products_with_invalid_metadata_json', $invalidjson);
        $report->add_inventory('invalid_promotion_amounts', $invalidamount);
        $report->add_inventory('invalid_promotion_windows', $invalidwindow);
        if ($invalidjson > 0) {
            $report->add_issue('important', 'invalid_product_metadata_json', 'Product merchandising metadata contains invalid JSON.', ['count' => $invalidjson]);
        }
    }
}
