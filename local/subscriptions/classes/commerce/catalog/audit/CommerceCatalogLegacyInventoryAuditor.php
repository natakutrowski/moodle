<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

/** Read-only E1 inventory of Native and Legacy catalogue structures. */
final class CommerceCatalogLegacyInventoryAuditor {
    private const TABLES = [
        'subscription_plan', 'subscription_plan_price', 'subscription_plan_translation',
        'subscription_access_scope', 'subscription_access_scope_translation', 'subscription_plan_entitlement',
        'subscription_digital_product', 'subscription_digital_product_lang',
        'local_subs_commerce_product', 'local_subs_commerce_prod_price', 'local_subs_commerce_prod_tr',
        'local_subs_commerce_prod_comp', 'local_subs_commerce_prod_ent', 'local_subs_commerce_prod_map',
    ];

    public function __construct(private readonly \moodle_database $db) {
    }

    public function audit(): CommerceCatalogLegacyInventoryReport {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = $this->db->get_manager()->table_exists($table)
                ? $this->db->count_records($table)
                : null;
        }

        $issues = [];
        foreach ($counts as $table => $count) {
            if ($count === null) {
                $issues[] = ['severity' => 'error', 'code' => 'missing_table', 'table' => $table];
            }
        }

        if (($counts['subscription_plan'] ?? 0) > 0 && ($counts['subscription_plan_price'] ?? 0) === 0) {
            $issues[] = ['severity' => 'warning', 'code' => 'plans_without_any_price'];
        }
        if (($counts['local_subs_commerce_product'] ?? 0) > 0 && ($counts['local_subs_commerce_prod_map'] ?? 0) === 0) {
            $issues[] = ['severity' => 'warning', 'code' => 'native_products_without_legacy_mapping'];
        }

        return new CommerceCatalogLegacyInventoryReport($counts, $issues);
    }
}
