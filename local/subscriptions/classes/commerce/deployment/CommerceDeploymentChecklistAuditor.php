<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\deployment;

use local_subscriptions\commerce\audit\CommerceIntegrityAuditReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\readiness\CommerceProductionReadinessAuditor;
use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the read-only 7.94I5 deployment checklist.
 */
final class CommerceDeploymentChecklistAuditor {
    /**
     * @param string[] $families
     * @param array<string, bool> $acknowledgements
     */
    public function audit(array $families, int $batchsize, array $acknowledgements): CommerceDeploymentChecklistReport {
        global $DB;

        $report = CommerceDeploymentChecklistReport::start([
            'families' => array_values($families),
            'batch_size' => $batchsize,
            'runtime_mode' => (string)get_config('local_subscriptions', 'commerce_runtime_mode'),
        ]);

        $readiness = (new CommerceProductionReadinessAuditor())->audit();
        $report->add_automated_check(
            'production_readiness',
            !empty($readiness['ready']),
            !empty($readiness['ready'])
                ? 'Production readiness audit passed.'
                : 'Production readiness audit contains blocking errors.',
            [
                'errors' => (int)($readiness['errors'] ?? 0),
                'warnings' => (int)($readiness['warnings'] ?? 0),
                'runtime_mode' => (string)($readiness['runtime_mode'] ?? ''),
            ]
        );

        $integrity = $this->audit_integrity($families, $batchsize);
        $integritydata = $integrity->to_array();
        $report->add_automated_check(
            'legacy_native_integrity',
            $integrity->is_ready(),
            $integrity->is_ready()
                ? 'Legacy-to-Native migration integrity passed.'
                : 'Legacy-to-Native migration integrity contains anomalies.',
            $integritydata['summary'] ?? []
        );

        $rollback = (new CommerceRuntimeRollbackService())->inspect();
        $target = $rollback['target'] ?? [];
        $rollbacksafe = ($target['runtime_mode'] ?? null) === 'legacy'
            && ($target['native_fallback_enabled'] ?? null) === true
            && ($target['shadow_enabled'] ?? null) === false
            && ($rollback['data_changes'] ?? null) === false;
        $report->add_automated_check(
            'guarded_runtime_rollback',
            $rollbacksafe,
            $rollbacksafe
                ? 'Guarded rollback targets Legacy without Commerce data changes.'
                : 'Guarded rollback target is not safe.',
            ['target' => $target, 'data_changes' => $rollback['data_changes'] ?? null]
        );

        $report->add_operator_acknowledgement(
            'backup_procedure',
            !empty($acknowledgements['backup_procedure']),
            'Database and moodledata backup procedure is documented and assigned.'
        );
        $report->add_operator_acknowledgement(
            'maintenance_procedure',
            !empty($acknowledgements['maintenance_procedure']),
            'Maintenance window and user communication procedure is documented.'
        );
        $report->add_operator_acknowledgement(
            'rollback_procedure',
            !empty($acknowledgements['rollback_procedure']),
            'Rollback decision, operator and command sequence are documented.'
        );
        $report->add_operator_acknowledgement(
            'smoke_test_procedure',
            !empty($acknowledgements['smoke_test_procedure']),
            'Post-switch Subscription and Digital smoke tests are documented.'
        );

        $report->finish();
        return $report;
    }

    /**
     * @param string[] $families
     */
    private function audit_integrity(array $families, int $batchsize): CommerceIntegrityAuditReport {
        global $DB;

        $registry = CommerceLegacyMigrationFactory::create_source_registry();
        $migrator = CommerceLegacyMigrationFactory::create_migrator();
        $report = CommerceIntegrityAuditReport::start($families);

        foreach ($families as $family) {
            $source = $registry->get($family);
            $report->set_legacy_total($family, $source->count());
            $afterid = 0;
            do {
                $ids = $source->get_ids($afterid, $batchsize);
                if ($ids === []) {
                    break;
                }
                $summary = $migrator->migrate_batch($family, $ids, false);
                $report->record_results($family, $summary->get_results());
                $afterid = max($ids);
            } while (true);
        }

        $report->set_native_integrity('duplicate_legacy_links', array_values($DB->get_records_sql(
            "SELECT MIN(id) AS id, legacyfamily, legacyid, COUNT(1) AS duplicatecount
               FROM {local_subscriptions_commerce_purchase}
              WHERE legacyfamily IN ('subscription', 'digital') AND legacyid > 0
           GROUP BY legacyfamily, legacyid
             HAVING COUNT(1) > 1"
        )));
        $report->set_native_integrity('duplicate_purchase_uuids', array_values($DB->get_records_sql(
            "SELECT MIN(id) AS id, purchaseuuid, COUNT(1) AS duplicatecount
               FROM {local_subscriptions_commerce_purchase}
              WHERE purchaseuuid IS NOT NULL AND purchaseuuid <> ''
           GROUP BY purchaseuuid
             HAVING COUNT(1) > 1"
        )));
        $report->set_native_integrity('duplicate_references', array_values($DB->get_records_sql(
            "SELECT MIN(id) AS id, reference, COUNT(1) AS duplicatecount
               FROM {local_subscriptions_commerce_purchase}
              WHERE reference IS NOT NULL AND reference <> ''
           GROUP BY reference
             HAVING COUNT(1) > 1"
        )));
        $report->set_native_integrity('orphan_legacy_links', array_values($DB->get_records_sql(
            "SELECT p.id, p.legacyfamily, p.legacyid, p.reference
               FROM {local_subscriptions_commerce_purchase} p
              WHERE (p.legacyfamily = 'subscription'
                     AND NOT EXISTS (SELECT 1 FROM {user_subscription} us WHERE us.id = p.legacyid))
                 OR (p.legacyfamily = 'digital'
                     AND NOT EXISTS (
                         SELECT 1
                           FROM {subscription_digital_payment_request} dp
                          WHERE dp.id = p.legacyid
                     ))"
        )));
        $report->finish();
        return $report;
    }
}
