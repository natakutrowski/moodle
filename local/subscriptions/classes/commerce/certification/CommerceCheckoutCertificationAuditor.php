<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Read-only database certification for checkout and purchase persistence.
 */
final class CommerceCheckoutCertificationAuditor {
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const ITEM = 'local_subscriptions_commerce_purchase_item';
    private const PAYMENT = 'local_subscriptions_commerce_payment';
    private const FULFILLMENT = 'local_subscriptions_commerce_fulfillment';

    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F7C');
        $manager = $this->db->get_manager();
        foreach ([self::PURCHASE, self::ITEM, self::PAYMENT, self::FULFILLMENT] as $table) {
            if (!$manager->table_exists($table)) {
                $report->add_issue('blocking', 'missing_table', 'Required checkout table is missing.', ['table' => $table]);
                return $report;
            }
        }

        $this->audit_orphans($report);
        $this->audit_totals($report);
        $this->audit_currency($report);
        $this->audit_payment_idempotency($report);
        $this->audit_fulfillment($report);
        return $report;
    }

    private function audit_orphans(CommerceCertificationReport $report): void {
        foreach ([self::ITEM => 'purchase_items', self::PAYMENT => 'payments', self::FULFILLMENT => 'fulfillments'] as $table => $label) {
            $count = $this->db->count_records_sql(
                'SELECT COUNT(1) FROM {' . $table . '} child
                  WHERE NOT EXISTS (SELECT 1 FROM {' . self::PURCHASE . '} p WHERE p.id = child.purchaseid)'
            );
            $report->add_inventory('orphan_' . $label, $count);
            if ($count > 0) {
                $report->add_issue('blocking', 'orphan_' . $label, 'Checkout child records reference missing purchases.', ['count' => $count]);
            }
        }
    }

    private function audit_totals(CommerceCertificationReport $report): void {
        $mismatches = $this->db->get_records_sql(
            'SELECT p.id, p.reference, p.subtotalminor, p.discountminor, p.totalminor,
                    COALESCE(SUM(i.grossminor), 0) AS itemgross,
                    COALESCE(SUM(i.discountminor), 0) AS itemdiscount,
                    COALESCE(SUM(i.netminor), 0) AS itemnet
               FROM {' . self::PURCHASE . '} p
          LEFT JOIN {' . self::ITEM . '} i ON i.purchaseid = p.id
           GROUP BY p.id, p.reference, p.subtotalminor, p.discountminor, p.totalminor
             HAVING p.subtotalminor <> COALESCE(SUM(i.grossminor), 0)
                 OR p.discountminor <> COALESCE(SUM(i.discountminor), 0)
                 OR p.totalminor <> COALESCE(SUM(i.netminor), 0)'
        );
        $report->add_inventory('purchase_total_mismatches', count($mismatches));
        foreach ($mismatches as $row) {
            $report->add_issue('blocking', 'purchase_total_mismatch', 'Purchase totals do not match immutable item snapshots.', ['purchaseid' => (int)$row->id, 'reference' => (string)$row->reference]);
        }
    }

    private function audit_currency(CommerceCertificationReport $report): void {
        $count = $this->db->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::ITEM . '} i
               JOIN {' . self::PURCHASE . '} p ON p.id = i.purchaseid
              WHERE i.currency <> p.currency'
        );
        $report->add_inventory('purchase_item_currency_mismatches', $count);
        if ($count > 0) {
            $report->add_issue('blocking', 'purchase_item_currency_mismatch', 'Purchase and item currencies differ.', ['count' => $count]);
        }
    }

    private function audit_payment_idempotency(CommerceCertificationReport $report): void {
        $duplicates = $this->db->get_records_sql(
            "SELECT provider, providerreference, COUNT(1) AS recordcount
               FROM {" . self::PAYMENT . "}
              WHERE providerreference IS NOT NULL AND providerreference <> ''
           GROUP BY provider, providerreference
             HAVING COUNT(1) > 1"
        );
        $report->add_inventory('duplicate_provider_references', count($duplicates));
        if ($duplicates) {
            $report->add_issue('blocking', 'duplicate_provider_reference', 'A provider payment reference is attached to several attempts.', ['count' => count($duplicates)]);
        }
    }

    private function audit_fulfillment(CommerceCertificationReport $report): void {
        $duplicatekeys = $this->db->get_records_sql(
            'SELECT idempotencykey, COUNT(1) AS recordcount
               FROM {' . self::FULFILLMENT . '}
           GROUP BY idempotencykey
             HAVING COUNT(1) > 1'
        );
        $report->add_inventory('duplicate_fulfillment_idempotency_keys', count($duplicatekeys));
        if ($duplicatekeys) {
            $report->add_issue('blocking', 'duplicate_fulfillment_idempotency_key', 'Fulfillment idempotency keys are not unique.', ['count' => count($duplicatekeys)]);
        }

        $missing = $this->db->count_records_sql(
            "SELECT COUNT(1)
               FROM {" . self::PURCHASE . "} p
              WHERE p.status = 'fulfilled'
                AND NOT EXISTS (SELECT 1 FROM {" . self::FULFILLMENT . "} f WHERE f.purchaseid = p.id AND f.status = 'completed')"
        );
        $report->add_inventory('fulfilled_purchases_without_completed_fulfillment', $missing);
        if ($missing > 0) {
            $report->add_issue('blocking', 'fulfilled_purchase_without_fulfillment', 'Fulfilled purchases lack a completed fulfillment record.', ['count' => $missing]);
        }
    }
}
