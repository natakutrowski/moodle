<?php

// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\deployment;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only deployment checklist report for phase 7.94I5.
 */
final class CommerceDeploymentChecklistReport {
    private const VERSION = 1;

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $metadata
     */
    public static function start(array $metadata = []): self {
        return new self([
            'format_version' => self::VERSION,
            'phase' => '7.94I5',
            'status' => 'running',
            'started_at' => time(),
            'finished_at' => null,
            'readonly' => true,
            'metadata' => $metadata,
            'automated_checks' => [],
            'operator_acknowledgements' => [],
            'summary' => [],
            'ready' => false,
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
    public function add_automated_check(string $name, bool $passed, string $message, array $evidence = []): void {
        $this->data['automated_checks'][$name] = [
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
            'evidence' => $evidence,
        ];
    }

    public function add_operator_acknowledgement(string $name, bool $acknowledged, string $message): void {
        $this->data['operator_acknowledgements'][$name] = [
            'status' => $acknowledged ? 'acknowledged' : 'pending',
            'message' => $message,
        ];
    }

    public function finish(): void {
        $automatedfailed = 0;
        foreach ($this->data['automated_checks'] as $check) {
            if (($check['status'] ?? '') !== 'passed') {
                $automatedfailed++;
            }
        }

        $acknowledgementspending = 0;
        foreach ($this->data['operator_acknowledgements'] as $acknowledgement) {
            if (($acknowledgement['status'] ?? '') !== 'acknowledged') {
                $acknowledgementspending++;
            }
        }

        $ready = $automatedfailed === 0 && $acknowledgementspending === 0;
        $this->data['summary'] = [
            'automated_checks' => count($this->data['automated_checks']),
            'automated_failed' => $automatedfailed,
            'operator_acknowledgements' => count($this->data['operator_acknowledgements']),
            'operator_acknowledgements_pending' => $acknowledgementspending,
        ];
        $this->data['ready'] = $ready;
        $this->data['status'] = $ready ? 'passed' : 'blocked';
        $this->data['finished_at'] = time();
    }

    public function is_ready(): bool {
        return !empty($this->data['ready']);
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
            throw new \RuntimeException('The deployment checklist report directory is not writable: ' . $directory);
        }

        $json = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $json, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to write the deployment checklist report atomically: ' . $path);
        }
    }
}
