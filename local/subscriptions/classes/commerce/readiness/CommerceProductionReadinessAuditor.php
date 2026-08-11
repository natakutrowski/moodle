<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\certification\CommerceCheckoutCertificationAuditor;
use local_subscriptions\commerce\certification\CommerceOwnershipCertificationAuditor;
use local_subscriptions\commerce\certification\CommercePricingCertificationAuditor;
use local_subscriptions\commerce\certification\CommerceStorefrontBaselineAuditor;
use local_subscriptions\commerce\certification\CommerceStorefrontUxCertificationAuditor;
use moodle_database;

/**
 * Aggregates F7 and F8 evidence into the final 7.95 production decision.
 */
final class CommerceProductionReadinessAuditor {
    public function __construct(
        private readonly moodle_database $db,
        private readonly string $moodleroot,
        private readonly string $pluginroot
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function audit(array $options): array {
        global $release, $version;
        $branch = trim((string)($options['branch'] ?? ''));
        $mode = trim((string)($options['mode'] ?? 'native'));
        $family = trim((string)($options['family'] ?? 'all'));
        $batchsize = (int)($options['batch_size'] ?? 100);

        $git = (new CommerceGitReadinessAuditor($this->moodleroot))->audit($branch)->to_array();
        $native = (new CommerceNativeReadinessAuditor($this->db))->audit($mode)->to_array();
        $backfill = (new CommerceBackfillReadinessAuditor($this->db))->audit($family, $batchsize)->to_array();
        $includebackuprollback = !array_key_exists('include_backup_rollback', $options)
            || !empty($options['include_backup_rollback']);
        $backup = null;
        if ($includebackuprollback) {
            $backup = (new CommerceBackupRollbackReadinessAuditor($this->moodleroot, $this->pluginroot))->audit(
                [
                    'database' => (string)($options['database_backup'] ?? ''),
                    'code' => (string)($options['code_backup'] ?? ''),
                    'moodledata' => (string)($options['moodledata_backup'] ?? ''),
                ],
                (string)($options['rollback_ref'] ?? ''),
                (int)($options['max_backup_age_hours'] ?? 24),
                (int)($options['minimum_free_gb'] ?? 5)
            )->to_array();
        }

        $baseline = (new CommerceStorefrontBaselineAuditor($this->db))->audit()->to_array();
        $pricing = (new CommercePricingCertificationAuditor($this->db))->audit()->to_array();
        $checkout = (new CommerceCheckoutCertificationAuditor($this->db))->audit()->to_array();
        $ownership = (new CommerceOwnershipCertificationAuditor($this->db))->audit()->to_array();
        $ux = (new CommerceStorefrontUxCertificationAuditor($this->pluginroot))->audit()->to_array();

        $phases = [
            'F8A Git' => $this->normalise($git),
            'F8B Native' => $this->normalise($native),
            'F8C Backfill' => $this->normalise($backfill),
            'F7A Baseline' => [
                'passed' => (bool)($baseline['certifiablebaseline'] ?? false),
                'summary' => $baseline['summary'] ?? [],
            ],
            'F7B Pricing' => $this->normalise($pricing),
            'F7C Checkout' => $this->normalise($checkout),
            'F7D Ownership' => $this->normalise($ownership),
            'F7F UX' => $this->normalise($ux),
        ];
        if ($includebackuprollback && $backup !== null) {
            $phases = array_slice($phases, 0, 3, true)
                + ['F8D Backup & rollback' => $this->normalise($backup)]
                + array_slice($phases, 3, null, true);
        }

        $ready = true;
        foreach ($phases as $phase) {
            $ready = $ready && !empty($phase['passed']);
        }

        $gitinventory = $git['inventory'] ?? [];
        return [
            'phase' => '7.95F8E',
            'generatedat' => time(),
            'readonly' => true,
            'ready' => $ready,
            'environment' => [
                'commerce_version' => '7.95',
                'moodle_release' => isset($release) ? (string)$release : null,
                'moodle_version' => isset($version) ? (string)$version : null,
                'php_version' => PHP_VERSION,
                'git_branch' => $gitinventory['branch'] ?? null,
                'git_commit' => $gitinventory['commit'] ?? null,
                'head_tags' => $gitinventory['head_tags'] ?? [],
            ],
            'phases' => $phases,
            'reports' => [
                'f8a_git' => $git,
                'f8b_native' => $native,
                'f8c_backfill' => $backfill,
                'f8d_backup_rollback' => $includebackuprollback ? $backup : null,
                'f7a_baseline' => $baseline,
                'f7b_pricing' => $pricing,
                'f7c_checkout' => $checkout,
                'f7d_ownership' => $ownership,
                'f7f_ux' => $ux,
            ],
        ];
    }

    /** @param array<string, mixed> $report @return array<string, mixed> */
    private function normalise(array $report): array {
        return [
            'passed' => (bool)($report['certifiable'] ?? false),
            'summary' => $report['summary'] ?? [],
        ];
    }
}
