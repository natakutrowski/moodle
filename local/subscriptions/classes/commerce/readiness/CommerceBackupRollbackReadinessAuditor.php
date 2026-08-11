<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\certification\CommerceCertificationReport;

/**
 * Verifies that fresh, readable backup evidence and rollback tooling exist.
 */
final class CommerceBackupRollbackReadinessAuditor {
    /** @var callable(string): array{code:int, output:string} */
    private $runner;

    /** @var callable(string): int|float|false */
    private $diskfree;

    /** @var callable(): int */
    private $clock;

    public function __construct(
        private readonly string $moodleroot,
        private readonly string $pluginroot,
        ?callable $runner = null,
        ?callable $diskfree = null,
        ?callable $clock = null
    ) {
        $this->runner = $runner ?? static function(string $command): array {
            $output = [];
            $code = 0;
            exec($command . ' 2>&1', $output, $code);
            return ['code' => $code, 'output' => trim(implode("\n", $output))];
        };
        $this->diskfree = $diskfree ?? static fn(string $path): int|float|false => disk_free_space($path);
        $this->clock = $clock ?? static fn(): int => time();
    }

    /**
     * @param array{database?:string,code?:string,moodledata?:string} $backups
     */
    public function audit(
        array $backups,
        string $rollbackref,
        int $maxagehours = 24,
        int $minimumfreegb = 5
    ): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F8D');
        $maxagehours = max(1, min(720, $maxagehours));
        $minimumfreegb = max(1, $minimumfreegb);

        $report->add_inventory('readonly', true);
        $report->add_inventory('max_backup_age_hours', $maxagehours);
        $report->add_inventory('minimum_free_space_gb', $minimumfreegb);
        $report->add_inventory('rollback_ref', $rollbackref);

        $details = [];
        foreach (['database', 'code', 'moodledata'] as $type) {
            $path = trim((string)($backups[$type] ?? ''));
            $detail = $this->inspect_backup($path, $maxagehours);
            $details[$type] = $detail;

            if ($path === '') {
                $report->add_issue('blocking', 'missing_' . $type . '_backup_evidence', ucfirst($type) . ' backup evidence was not supplied.');
                continue;
            }
            if (!$detail['readable']) {
                $report->add_issue('blocking', 'unreadable_' . $type . '_backup', ucfirst($type) . ' backup is missing or unreadable.', ['path' => $path]);
                continue;
            }
            if (!$detail['nonempty']) {
                $report->add_issue('blocking', 'empty_' . $type . '_backup', ucfirst($type) . ' backup is empty.', ['path' => $path]);
            }
            if (!$detail['fresh']) {
                $report->add_issue('blocking', 'stale_' . $type . '_backup', ucfirst($type) . ' backup is older than the accepted threshold.', [
                    'path' => $path,
                    'age_hours' => $detail['age_hours'],
                    'maximum_hours' => $maxagehours,
                ]);
            }
        }
        $report->add_inventory('backups', $details);

        $freebytes = ($this->diskfree)($this->moodleroot);
        $freegb = $freebytes === false ? null : round(((float)$freebytes) / 1073741824, 2);
        $report->add_inventory('free_space_gb', $freegb);
        if ($freegb === null) {
            $report->add_issue('important', 'free_space_unknown', 'Free disk space could not be measured.', ['root' => $this->moodleroot]);
        } else if ($freegb < $minimumfreegb) {
            $report->add_issue('blocking', 'insufficient_free_space', 'Free disk space is below the configured safety threshold.', [
                'available_gb' => $freegb,
                'minimum_gb' => $minimumfreegb,
            ]);
        }

        $requiredrollbackfiles = [
            'cli/commerce/operations/rollback_commerce_runtime.php',
            'cli/commerce/operations/set_commerce_runtime_mode.php',
            'cli/commerce/migration/repair_native_purchase.php',
        ];
        $missingfiles = [];
        foreach ($requiredrollbackfiles as $relativepath) {
            if (!is_readable($this->pluginroot . '/' . $relativepath)) {
                $missingfiles[] = $relativepath;
            }
        }
        $report->add_inventory('required_rollback_files', $requiredrollbackfiles);
        $report->add_inventory('missing_rollback_files', $missingfiles);
        if ($missingfiles !== []) {
            $report->add_issue('blocking', 'missing_rollback_tooling', 'Rollback tooling is incomplete.', ['files' => $missingfiles]);
        }

        $rollbackref = trim($rollbackref);
        $validref = false;
        $resolvedcommit = null;
        if ($rollbackref !== '') {
            $command = 'git -C ' . escapeshellarg($this->moodleroot)
                . ' rev-parse --verify ' . escapeshellarg($rollbackref . '^{commit}');
            $result = ($this->runner)($command);
            $validref = $result['code'] === 0 && preg_match('/^[a-f0-9]{40}$/i', trim($result['output'])) === 1;
            $resolvedcommit = $validref ? strtolower(trim($result['output'])) : null;
        }
        $report->add_inventory('rollback_ref_valid', $validref);
        $report->add_inventory('rollback_commit', $resolvedcommit);
        if (!$validref) {
            $report->add_issue('blocking', 'invalid_rollback_ref', 'Rollback Git reference is missing or does not resolve to a commit.', [
                'rollback_ref' => $rollbackref,
            ]);
        }

        return $report;
    }

    /** @return array<string, mixed> */
    private function inspect_backup(string $path, int $maxagehours): array {
        if ($path === '' || !is_readable($path)) {
            return [
                'path' => $path,
                'readable' => false,
                'nonempty' => false,
                'size_bytes' => null,
                'modified_at' => null,
                'age_hours' => null,
                'fresh' => false,
            ];
        }

        $size = is_file($path) ? filesize($path) : $this->directory_size($path);
        $modified = filemtime($path);
        $agehours = $modified === false ? null : round((($this->clock)() - $modified) / 3600, 2);

        return [
            'path' => $path,
            'readable' => true,
            'nonempty' => $size !== false && $size > 0,
            'size_bytes' => $size === false ? null : $size,
            'modified_at' => $modified === false ? null : $modified,
            'age_hours' => $agehours,
            'fresh' => $agehours !== null && $agehours >= 0 && $agehours <= $maxagehours,
        ];
    }

    private function directory_size(string $path): int|false {
        if (!is_dir($path)) {
            return false;
        }
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}
