<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\certification\CommerceCertificationReport;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;
use moodle_database;
use xmldb_table;

/**
 * Read-only Native Commerce production-readiness audit for 7.95F8B.
 */
final class CommerceNativeReadinessAuditor {
    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(string $expectedmode = CommerceRuntimeMode::NATIVE): CommerceCertificationReport {
        global $CFG;

        $report = new CommerceCertificationReport('7.95F8B');
        $mode = CommerceRuntimeMode::normalize((string)get_config('local_subscriptions', 'commerce_runtime_mode'));
        $readmode = strtolower(trim((string)get_config('local_subscriptions', 'commerce_runtime_read_mode')));
        $fallback = (bool)get_config('local_subscriptions', 'commerce_runtime_native_fallback_enabled');
        $checkoutenabled = (bool)get_config('local_subscriptions', 'commerce_checkout_enabled');

        $report->add_inventory('runtime_mode', $mode);
        $report->add_inventory('expected_runtime_mode', $expectedmode);
        $report->add_inventory('runtime_read_mode', $readmode);
        $report->add_inventory('native_fallback_enabled', $fallback);
        $report->add_inventory('checkout_enabled', $checkoutenabled);

        if ($mode !== $expectedmode) {
            $report->add_issue('blocking', 'unexpected_runtime_mode', 'Commerce Runtime does not use the expected production mode.', [
                'expected' => $expectedmode,
                'actual' => $mode,
            ]);
        }
        if (!in_array($readmode, ['native', 'auto'], true)) {
            $report->add_issue('important', 'non_native_read_mode', 'Commerce read mode is not Native or Auto.', ['actual' => $readmode]);
        }
        if (!$checkoutenabled) {
            $report->add_issue('blocking', 'checkout_disabled', 'The Commerce checkout feature is disabled.', []);
        }
        if (!$fallback) {
            $report->add_issue('important', 'native_fallback_disabled', 'Native fallback is disabled; rollback options are reduced.', []);
        }

        $requiredclasses = [
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher::class,
            \local_subscriptions\commerce\runtime\switching\CommerceNativeRuntimeExecutor::class,
            \local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory::class,
            \local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry::class,
            \local_subscriptions\commerce\checkout\CommerceCheckoutService::class,
        ];
        $missingclasses = array_values(array_filter($requiredclasses, static fn(string $class): bool => !class_exists($class)));
        $report->add_inventory('required_classes', count($requiredclasses));
        $report->add_inventory('missing_classes', $missingclasses);
        if ($missingclasses !== []) {
            $report->add_issue('blocking', 'missing_native_classes', 'Required Native Commerce classes cannot be autoloaded.', ['classes' => $missingclasses]);
        }

        $requiredfiles = [
            'cli/commerce/operations/set_commerce_runtime_mode.php',
            'cli/commerce/operations/rollback_commerce_runtime.php',
            'cli/commerce/migration/migrate_legacy_commerce_purchases.php',
            'cli/commerce/migration/audit_commerce_native_backfill.php',
            'cli/commerce/certify_795f7.php',
        ];
        $pluginroot = $CFG->dirroot . '/local/subscriptions';
        $missingfiles = array_values(array_filter($requiredfiles, static fn(string $file): bool => !is_file($pluginroot . '/' . $file)));
        $report->add_inventory('required_files', count($requiredfiles));
        $report->add_inventory('missing_files', $missingfiles);
        if ($missingfiles !== []) {
            $report->add_issue('blocking', 'missing_operational_files', 'Required Native Commerce operational tools are missing.', ['files' => $missingfiles]);
        }

        $tables = [
            CommercePersistenceSchema::TABLE_PURCHASE,
            CommercePersistenceSchema::TABLE_ITEM,
            CommercePersistenceSchema::TABLE_PAYMENT,
            CommercePersistenceSchema::TABLE_FULFILLMENT,
            'local_subs_commerce_product',
            'local_subs_commerce_prod_price',
            'local_subs_commerce_prod_map',
            'local_subs_commerce_prod_ent',
        ];
        $manager = $this->db->get_manager();
        $missingtables = [];
        foreach ($tables as $table) {
            if (!$manager->table_exists(new xmldb_table($table))) {
                $missingtables[] = $table;
            }
        }
        $report->add_inventory('required_tables', count($tables));
        $report->add_inventory('missing_tables', $missingtables);
        if ($missingtables !== []) {
            $report->add_issue('blocking', 'missing_native_tables', 'Required Native Commerce tables are missing.', ['tables' => $missingtables]);
            return $report;
        }

        $activeproducts = $this->db->count_records('local_subs_commerce_product', ['status' => 'active']);
        $activeprices = $this->db->count_records('local_subs_commerce_prod_price', ['active' => 1]);
        $mappings = $this->db->count_records('local_subs_commerce_prod_map');
        $orphanmappings = $this->db->count_records_sql(
            'SELECT COUNT(1) FROM {local_subs_commerce_prod_map} m
              LEFT JOIN {local_subs_commerce_product} p ON p.id = m.productid
             WHERE p.id IS NULL'
        );
        $report->add_inventory('active_products', $activeproducts);
        $report->add_inventory('active_prices', $activeprices);
        $report->add_inventory('legacy_mappings', $mappings);
        $report->add_inventory('orphan_mappings', $orphanmappings);

        if ($activeproducts === 0) {
            $report->add_issue('blocking', 'empty_active_catalog', 'The Native catalog contains no active product.', []);
        }
        if ($activeprices === 0) {
            $report->add_issue('blocking', 'empty_active_pricing', 'The Native catalog contains no active price.', []);
        }
        if ($orphanmappings > 0) {
            $report->add_issue('blocking', 'orphan_catalog_mappings', 'Catalog mappings reference missing Native products.', ['count' => $orphanmappings]);
        }

        return $report;
    }
}
