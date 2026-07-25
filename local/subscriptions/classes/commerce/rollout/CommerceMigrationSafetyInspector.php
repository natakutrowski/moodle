<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\rollout;

defined('MOODLE_INTERNAL') || die();

final class CommerceMigrationSafetyInspector {
    public function inspect(string $pluginroot): CommerceMigrationSafetyReport {
        global $DB;

        $dbmanager = $DB->get_manager();
        $legacytables = [
            'user_subscription',
            'subscription_payment_request',
            'subscription_digital_payment_request',
        ];

        $legacytablesavailable = true;

        foreach ($legacytables as $tablename) {
            if (!$dbmanager->table_exists(new \xmldb_table($tablename))) {
                $legacytablesavailable = false;
                break;
            }
        }

        $pluginroot = rtrim($pluginroot, '/');
        $settings = @file_get_contents($pluginroot . '/settings.php') ?: '';
        $upgrade = @file_get_contents($pluginroot . '/db/upgrade.php') ?: '';

        $canonicalflagavailable = str_contains(
            $settings,
            'commerce_native_dual_write_enabled'
        );

        $legacyaliasavailable = str_contains(
            $settings,
            'commerce_dual_write_enabled'
        );

        $upgradebridgeavailable = str_contains(
            $upgrade,
            'commerce_native_dual_write_enabled'
        ) && str_contains(
            $upgrade,
            'commerce_dual_write_enabled'
        );

        $fallbackenabled = (bool) get_config(
            'local_subscriptions',
            'commerce_native_legacy_fallback_enabled'
        );

        return new CommerceMigrationSafetyReport(
            $legacytablesavailable,
            $canonicalflagavailable,
            $legacyaliasavailable,
            $upgradebridgeavailable,
            $fallbackenabled,
            ['No Legacy table, bridge or alias is removed by I10F.']
        );
    }
}
