<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;

/** Read-only Legacy-to-Native Commerce integrity report. */
final class CommerceIntegrityAuditReport {
    private const VERSION = 1;

    private array $data;

    /** @param string[] $families */
    public static function start(array $families): self {
        $familydata = [];
        foreach ($families as $family) {
            $familydata[$family] = [
                'legacy_total' => 0,
                'checked' => 0,
                'healthy' => 0,
                'missing_native' => 0,
                'mismatched' => 0,
                'failed' => 0,
                'counters' => [],
            ];
        }
        return new self([
            'format_version' => self::VERSION,
            'status' => 'running',
            'started_at' => time(),
            'finished_at' => null,
            'families' => $familydata,
            'native_integrity' => [
                'duplicate_legacy_links' => [],
                'duplicate_purchase_uuids' => [],
                'duplicate_references' => [],
                'orphan_legacy_links' => [],
            ],
            'anomalies' => [],
            'summary' => [],
            'ready' => false,
        ]);
    }

    private function __construct(array $data) {
        $this->data = $data;
    }

    public function set_legacy_total(string $family, int $total): void {
        $this->require_family($family);
        $this->data['families'][$family]['legacy_total'] = max(0, $total);
    }

    /** @param CommerceLegacyMigrationResult[] $results */
    public function record_results(string $family, array $results): void {
        $this->require_family($family);
        foreach ($results as $result) {
            if (!$result instanceof CommerceLegacyMigrationResult) {
                throw new \InvalidArgumentException('Integrity results must use CommerceLegacyMigrationResult.');
            }
            $status = $result->get_status();
            $this->data['families'][$family]['checked']++;
            $this->data['families'][$family]['counters'][$status] =
                (int)($this->data['families'][$family]['counters'][$status] ?? 0) + 1;

            if ($status === CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT) {
                $this->data['families'][$family]['healthy']++;
                continue;
            }
            if ($status === CommerceLegacyMigrationResult::STATUS_DRY_RUN) {
                $this->data['families'][$family]['missing_native']++;
            } else if ($status === CommerceLegacyMigrationResult::STATUS_INVALID) {
                $this->data['families'][$family]['mismatched']++;
            } else {
                $this->data['families'][$family]['failed']++;
            }
            $this->data['anomalies'][] = $result->to_array();
        }
    }

    public function set_native_integrity(string $key, array $rows): void {
        if (!array_key_exists($key, $this->data['native_integrity'])) {
            throw new \InvalidArgumentException('Unknown native integrity check: ' . $key);
        }
        $this->data['native_integrity'][$key] = array_values($rows);
    }

    public function finish(): void {
        $legacytotal = 0;
        $checked = 0;
        $healthy = 0;
        $missing = 0;
        $mismatched = 0;
        $failed = 0;
        foreach ($this->data['families'] as $family) {
            $legacytotal += (int)$family['legacy_total'];
            $checked += (int)$family['checked'];
            $healthy += (int)$family['healthy'];
            $missing += (int)$family['missing_native'];
            $mismatched += (int)$family['mismatched'];
            $failed += (int)$family['failed'];
        }
        $duplicates = count($this->data['native_integrity']['duplicate_legacy_links'])
            + count($this->data['native_integrity']['duplicate_purchase_uuids'])
            + count($this->data['native_integrity']['duplicate_references']);
        $orphans = count($this->data['native_integrity']['orphan_legacy_links']);
        $ready = $missing === 0 && $mismatched === 0 && $failed === 0 && $duplicates === 0 && $orphans === 0;
        $this->data['summary'] = [
            'legacy_total' => $legacytotal,
            'checked' => $checked,
            'healthy' => $healthy,
            'missing_native' => $missing,
            'mismatched' => $mismatched,
            'failed' => $failed,
            'duplicate_groups' => $duplicates,
            'orphan_native_purchases' => $orphans,
            'anomalies' => count($this->data['anomalies']),
        ];
        $this->data['ready'] = $ready;
        $this->data['status'] = $ready ? 'passed' : 'failed';
        $this->data['finished_at'] = time();
    }

    public function is_ready(): bool {
        return !empty($this->data['ready']);
    }

    public function to_array(): array {
        return $this->data;
    }

    public function save(string $path): void {
        $directory = dirname($path);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('The integrity report directory is not writable: ' . $directory);
        }
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to write the integrity report atomically: ' . $path);
        }
    }

    private function require_family(string $family): void {
        if (!isset($this->data['families'][$family])) {
            throw new \InvalidArgumentException('Unknown integrity audit family: ' . $family);
        }
    }
}
