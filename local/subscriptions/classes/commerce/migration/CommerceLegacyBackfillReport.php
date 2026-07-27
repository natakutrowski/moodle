<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Durable, file-backed execution report and checkpoint for the Legacy Commerce backfill. */
final class CommerceLegacyBackfillReport {
    private const VERSION = 1;

    private array $data;

    /** @param string[] $families */
    public static function start(
        string $sessionid,
        bool $execute,
        array $families,
        int $batchsize,
        int $afterid,
        int $limit
    ): self {
        $sessionid = trim($sessionid);
        if ($sessionid === '') {
            throw new \InvalidArgumentException('A backfill session identifier is required.');
        }

        $familydata = [];
        foreach ($families as $family) {
            $familydata[$family] = [
                'initial_after_id' => $afterid,
                'last_processed_id' => $afterid,
                'processed' => 0,
                'batches' => 0,
                'complete' => false,
                'counters' => [],
            ];
        }

        return new self([
            'format_version' => self::VERSION,
            'session_id' => $sessionid,
            'mode' => $execute ? 'execute' : 'dry_run',
            'status' => 'running',
            'started_at' => time(),
            'updated_at' => time(),
            'finished_at' => null,
            'batch_size' => $batchsize,
            'limit_per_family' => $limit,
            'families' => $familydata,
            'errors' => [],
        ]);
    }

    public static function load(string $path): self {
        if (!is_readable($path)) {
            throw new \RuntimeException('The backfill report is not readable: ' . $path);
        }
        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || ($decoded['format_version'] ?? null) !== self::VERSION) {
            throw new \RuntimeException('Unsupported or invalid backfill report format.');
        }
        return new self($decoded);
    }

    private function __construct(array $data) {
        $this->data = $data;
    }

    /** @param string[] $families */
    public function assert_compatible(bool $execute, array $families, int $batchsize, int $limit): void {
        $expectedmode = $execute ? 'execute' : 'dry_run';
        if (($this->data['mode'] ?? '') !== $expectedmode) {
            throw new \RuntimeException('The resume report mode does not match the requested execution mode.');
        }
        $reportedfamilies = array_keys($this->data['families'] ?? []);
        sort($reportedfamilies);
        $families = array_values($families);
        sort($families);
        if ($reportedfamilies !== $families) {
            throw new \RuntimeException('The resume report families do not match the requested families.');
        }
        if ((int)($this->data['batch_size'] ?? 0) !== $batchsize) {
            throw new \RuntimeException('The resume report batch size does not match the requested batch size.');
        }
        if ((int)($this->data['limit_per_family'] ?? 0) !== $limit) {
            throw new \RuntimeException('The resume report limit does not match the requested limit.');
        }
    }

    public function get_session_id(): string {
        return (string)$this->data['session_id'];
    }

    public function get_last_processed_id(string $family): int {
        return (int)($this->data['families'][$family]['last_processed_id'] ?? 0);
    }

    public function get_processed(string $family): int {
        return (int)($this->data['families'][$family]['processed'] ?? 0);
    }

    public function is_family_complete(string $family): bool {
        return !empty($this->data['families'][$family]['complete']);
    }

    public function record_batch(string $family, array $results, int $lastprocessedid): void {
        if (!isset($this->data['families'][$family])) {
            throw new \InvalidArgumentException('Unknown backfill report family: ' . $family);
        }
        $this->data['families'][$family]['batches']++;
        $this->data['families'][$family]['processed'] += count($results);
        $this->data['families'][$family]['last_processed_id'] = $lastprocessedid;
        foreach ($results as $result) {
            if (!$result instanceof CommerceLegacyMigrationResult) {
                throw new \InvalidArgumentException('Backfill report results must use CommerceLegacyMigrationResult.');
            }
            $status = $result->get_status();
            $current = (int)($this->data['families'][$family]['counters'][$status] ?? 0);
            $this->data['families'][$family]['counters'][$status] = $current + 1;
            if (!$result->is_successful()) {
                $this->data['errors'][] = $result->to_array();
            }
        }
        $this->touch();
    }

    public function mark_family_complete(string $family): void {
        $this->data['families'][$family]['complete'] = true;
        $this->touch();
    }

    public function finish(bool $success): void {
        $this->data['status'] = $success ? 'completed' : 'completed_with_errors';
        $this->data['finished_at'] = time();
        $this->touch();
    }

    public function save(string $path): void {
        $directory = dirname($path);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('The backfill report directory is not writable: ' . $directory);
        }
        $json = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to write the backfill report atomically: ' . $path);
        }
    }

    public function to_array(): array {
        return $this->data;
    }

    private function touch(): void {
        $this->data['updated_at'] = time();
    }
}
