<?php

namespace local_subscriptions\commerce\certification;

use moodle_database;

/**
 * Read-only certification for product entitlements, grants and access ledgers.
 */
final class CommerceOwnershipCertificationAuditor {
    private const PRODUCT = 'local_subs_commerce_product';
    private const ENTITLEMENT = 'local_subs_commerce_prod_ent';
    private const GRANT = 'local_subs_commerce_grant';
    private const DIGITAL = 'local_subs_commerce_dig_access';
    private const STATE = 'local_subs_commerce_ful_state';
    private const PURCHASE = 'local_subscriptions_commerce_purchase';

    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F7D');
        $manager = $this->db->get_manager();
        foreach ([self::PRODUCT, self::ENTITLEMENT, self::GRANT, self::DIGITAL, self::STATE, self::PURCHASE] as $table) {
            if (!$manager->table_exists($table)) {
                $report->add_issue('blocking', 'missing_table', 'Required ownership table is missing.', ['table' => $table]);
                return $report;
            }
        }

        $this->audit_entitlements($report);
        $this->audit_grants($report);
        $this->audit_digital_access($report);
        $this->audit_fulfillment_states($report);
        return $report;
    }

    private function audit_entitlements(CommerceCertificationReport $report): void {
        $orphans = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::ENTITLEMENT . '} e
              WHERE NOT EXISTS (SELECT 1 FROM {' . self::PRODUCT . '} p WHERE p.id = e.productid)'
        );
        $report->add_inventory('orphan_product_entitlements', $orphans);
        if ($orphans > 0) {
            $report->add_issue('blocking', 'orphan_product_entitlement', 'Entitlement definitions reference missing products.', ['count' => $orphans]);
        }

        $duplicates = $this->db->get_records_sql(
            'SELECT productid, type, resourcekey, COUNT(1) AS recordcount
               FROM {' . self::ENTITLEMENT . '}
           GROUP BY productid, type, resourcekey
             HAVING COUNT(1) > 1'
        );
        $report->add_inventory('duplicate_entitlement_definitions', count($duplicates));
        if ($duplicates) {
            $report->add_issue('blocking', 'duplicate_entitlement_definition', 'Equivalent entitlement definitions are duplicated.', ['count' => count($duplicates)]);
        }
    }

    private function audit_grants(CommerceCertificationReport $report): void {
        $invalidwindow = $this->db->count_records_select(self::GRANT, 'validuntil IS NOT NULL AND validuntil < validfrom');
        $report->add_inventory('invalid_grant_windows', $invalidwindow);
        if ($invalidwindow > 0) {
            $report->add_issue('blocking', 'invalid_grant_window', 'Grant validity ends before it starts.', ['count' => $invalidwindow]);
        }

        $missingpurchase = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::GRANT . '} g
              WHERE NOT EXISTS (SELECT 1 FROM {' . self::PURCHASE . '} p WHERE p.reference = g.purchasereference)'
        );
        $report->add_inventory('grants_without_purchase', $missingpurchase);
        if ($missingpurchase > 0) {
            $report->add_issue('important', 'grant_without_purchase', 'Grant ledger entries reference purchases not present in the Native aggregate.', ['count' => $missingpurchase]);
        }

        $missingproduct = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::GRANT . '} g
              WHERE NOT EXISTS (SELECT 1 FROM {' . self::PRODUCT . '} p WHERE p.sku = g.productsku)'
        );
        $report->add_inventory('grants_without_product', $missingproduct);
        if ($missingproduct > 0) {
            $report->add_issue('important', 'grant_without_product', 'Grant ledger entries reference missing product SKUs.', ['count' => $missingproduct]);
        }

        $invalidbeneficiary = $this->db->count_records_select(self::GRANT, "beneficiaryuserid IS NULL AND (beneficiaryemail IS NULL OR beneficiaryemail = '')");
        $report->add_inventory('grants_without_beneficiary', $invalidbeneficiary);
        if ($invalidbeneficiary > 0) {
            $report->add_issue('blocking', 'grant_without_beneficiary', 'Grant entries require a user or email beneficiary.', ['count' => $invalidbeneficiary]);
        }
    }

    private function audit_digital_access(CommerceCertificationReport $report): void {
        $orphan = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::DIGITAL . '} d
              WHERE NOT EXISTS (SELECT 1 FROM {' . self::GRANT . '} g WHERE g.grantreference = d.grantreference)'
        );
        $report->add_inventory('digital_access_without_grant', $orphan);
        if ($orphan > 0) {
            $report->add_issue('blocking', 'digital_access_without_grant', 'Digital access records require a matching grant.', ['count' => $orphan]);
        }

        $overused = $this->db->count_records_select(self::DIGITAL, 'maxdownloads IS NOT NULL AND downloadcount > maxdownloads');
        $report->add_inventory('digital_access_over_download_limit', $overused);
        if ($overused > 0) {
            $report->add_issue('blocking', 'digital_download_limit_exceeded', 'Stored download counts exceed configured limits.', ['count' => $overused]);
        }
    }

    private function audit_fulfillment_states(CommerceCertificationReport $report): void {
        $orphan = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::STATE . '} s
              WHERE NOT EXISTS (SELECT 1 FROM {' . self::GRANT . '} g WHERE g.grantreference = s.grantreference)'
        );
        $report->add_inventory('fulfillment_states_without_grant', $orphan);
        if ($orphan > 0) {
            $report->add_issue('blocking', 'fulfillment_state_without_grant', 'Fulfillment states require a matching grant.', ['count' => $orphan]);
        }
    }
}
