<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Final read-only production certification report for phase 7.94I6.
 */
final class CommerceProductionCertificationReport {
    private const VERSION = 1;

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $metadata
     */
    public static function start(array $metadata): self {
        return new self([
            'format_version' => self::VERSION,
            'phase' => '7.94I6',
            'status' => 'running',
            'certification_id' => self::create_certification_id($metadata),
            'started_at' => time(),
            'finished_at' => null,
            'readonly' => true,
            'metadata' => $metadata,
            'checks' => [],
            'summary' => [],
            'certified' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $evidence
     */
    public function add_check(string $name, bool $passed, string $message, array $evidence = []): void {
        $this->data['checks'][$name] = [
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
            'evidence' => $evidence,
        ];
    }

    public function finish(): void {
        $failed = 0;
        foreach ($this->data['checks'] as $check) {
            if (($check['status'] ?? '') !== 'passed') {
                $failed++;
            }
        }

        $certified = $failed === 0 && $this->data['checks'] !== [];
        $this->data['summary'] = [
            'checks' => count($this->data['checks']),
            'failed' => $failed,
            'passed' => count($this->data['checks']) - $failed,
        ];
        $this->data['certified'] = $certified;
        $this->data['status'] = $certified ? 'passed' : 'blocked';
        $this->data['finished_at'] = time();
    }

    public function is_certified(): bool {
        return !empty($this->data['certified']);
    }

    /**
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return $this->data;
    }

    public function save(string $path): void {
        $directory = dirname($path);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('The production certification report directory is not writable: ' . $directory);
        }

        $json = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to write the production certification report atomically: ' . $path);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private static function create_certification_id(array $metadata): string {
        $commit = preg_replace('/[^a-f0-9]/i', '', (string)($metadata['git_commit'] ?? ''));
        $suffix = $commit !== '' ? substr(strtolower($commit), 0, 12) : 'unidentified';
        return 'CFR-COMMERCE-7.94I6-' . gmdate('Ymd-His') . '-' . $suffix;
    }
}
