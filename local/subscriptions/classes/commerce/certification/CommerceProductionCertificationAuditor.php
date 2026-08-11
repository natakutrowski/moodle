<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

use local_subscriptions\commerce\deployment\CommerceDeploymentChecklistAuditor;
use local_subscriptions\commerce\migration\CommerceLegacyBackfillReport;
use local_subscriptions\commerce\migration\CommerceTargetedNativeRepairService;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the final read-only 7.94I6 production certification.
 */
final class CommerceProductionCertificationAuditor {
    /**
     * @param string[] $families
     * @param array<string, bool> $acknowledgements
     * @param array<string, string> $releaseidentity
     */
    public function audit(
        array $families,
        int $batchsize,
        array $acknowledgements,
        bool $phpunitpassed,
        array $releaseidentity
    ): CommerceProductionCertificationReport {
        global $CFG;

        $metadata = [
            'families' => array_values($families),
            'batch_size' => $batchsize,
            'runtime_mode' => (string)get_config('local_subscriptions', 'commerce_runtime_mode'),
            'runtime_read_mode' => (string)get_config('local_subscriptions', 'commerce_runtime_read_mode'),
            'native_fallback_enabled' => (bool)get_config(
                'local_subscriptions',
                'commerce_runtime_native_fallback_enabled'
            ),
            'git_branch' => trim((string)($releaseidentity['git_branch'] ?? '')),
            'git_commit' => strtolower(trim((string)($releaseidentity['git_commit'] ?? ''))),
            'operator' => trim((string)($releaseidentity['operator'] ?? '')),
        ];
        $report = CommerceProductionCertificationReport::start($metadata);

        $deployment = (new CommerceDeploymentChecklistAuditor())->audit(
            $families,
            $batchsize,
            $acknowledgements
        );
        $deploymentdata = $deployment->to_array();
        $report->add_check(
            'i1_i3_i4_i5_deployment_checklist',
            $deployment->is_ready(),
            $deployment->is_ready()
                ? 'Production readiness, integrity, rollback and deployment checklist passed.'
                : 'The deployment checklist is blocked.',
            [
                'status' => $deploymentdata['status'] ?? 'unknown',
                'summary' => $deploymentdata['summary'] ?? [],
                'automated_checks' => $deploymentdata['automated_checks'] ?? [],
                'operator_acknowledgements' => $deploymentdata['operator_acknowledgements'] ?? [],
            ]
        );

        $requiredbackfillfiles = [
            'cli/commerce/migration/migrate_legacy_commerce_purchases.php',
            'classes/commerce/migration/CommerceLegacyBackfillReport.php',
            'classes/commerce/migration/CommerceLegacyPurchaseMigrator.php',
        ];
        $missingbackfillfiles = $this->missing_plugin_files($requiredbackfillfiles);
        $backfillready = $missingbackfillfiles === [] && class_exists(CommerceLegacyBackfillReport::class);
        $report->add_check(
            'i2_industrial_backfill_tooling',
            $backfillready,
            $backfillready
                ? 'Industrial backfill tooling and checkpoint report are available.'
                : 'Industrial backfill tooling is incomplete.',
            ['required_files' => $requiredbackfillfiles, 'missing_files' => $missingbackfillfiles]
        );

        $repairfile = 'cli/commerce/migration/repair_native_purchase.php';
        $repairready = class_exists(CommerceTargetedNativeRepairService::class)
            && is_readable($CFG->dirroot . '/local/subscriptions/' . $repairfile);
        $report->add_check(
            'i3a_targeted_repair_wheel',
            $repairready,
            $repairready
                ? 'Targeted Native repair wheel is available.'
                : 'Targeted Native repair wheel is unavailable.',
            ['cli' => $repairfile, 'service' => CommerceTargetedNativeRepairService::class]
        );

        $report->add_check(
            'phpunit_attestation',
            $phpunitpassed,
            $phpunitpassed
                ? 'The operator attests that the final local_subscriptions PHPUnit suite passed.'
                : 'Final PHPUnit execution has not been attested.',
            ['operator_attested' => $phpunitpassed]
        );

        $gitbranch = $metadata['git_branch'];
        $gitcommit = $metadata['git_commit'];
        $operator = $metadata['operator'];
        $identityvalid = $gitbranch !== ''
            && preg_match('/^[a-f0-9]{40}$/', $gitcommit) === 1
            && $operator !== '';
        $report->add_check(
            'release_identity',
            $identityvalid,
            $identityvalid
                ? 'Release branch, immutable Git commit and operator are identified.'
                : 'Release identity is incomplete or the Git commit is not a full 40-character SHA-1.',
            ['git_branch' => $gitbranch, 'git_commit' => $gitcommit, 'operator' => $operator]
        );

        $runtimeisshadow = $metadata['runtime_mode'] === 'shadow';
        $runtimeisnativerollout = $metadata['runtime_mode'] === 'native'
            && $metadata['runtime_read_mode'] === 'native'
            && $metadata['native_fallback_enabled'] === true;
        $runtimestatecertifiable = $runtimeisshadow || $runtimeisnativerollout;

        $report->add_check(
            'code_freeze_runtime_state',
            $runtimestatecertifiable,
            $runtimeisshadow
                ? 'Runtime remains in Shadow mode at pre-production certification time.'
                : (
                    $runtimeisnativerollout
                        ? 'Runtime and reads are Native with guarded Legacy fallback enabled.'
                        : 'Runtime must be Shadow, or fully Native with Native reads and guarded Legacy fallback enabled.'
                ),
            [
                'runtime_mode' => $metadata['runtime_mode'],
                'runtime_read_mode' => $metadata['runtime_read_mode'],
                'native_fallback_enabled' => $metadata['native_fallback_enabled'],
            ]
        );

        $report->finish();
        return $report;
    }

    /**
     * @param string[] $relativepaths
     * @return string[]
     */
    private function missing_plugin_files(array $relativepaths): array {
        global $CFG;

        $missing = [];
        foreach ($relativepaths as $relativepath) {
            if (!is_readable($CFG->dirroot . '/local/subscriptions/' . $relativepath)) {
                $missing[] = $relativepath;
            }
        }
        return $missing;
    }
}
