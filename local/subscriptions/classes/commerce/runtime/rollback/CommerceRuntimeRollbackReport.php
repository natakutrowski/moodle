<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\rollback;

/**
 * Builds and atomically persists a runtime rollback report.
 */
final class CommerceRuntimeRollbackReport {
    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public static function build(string $mode, string $status, array $result, ?string $error = null): array {
        $report = [
            'format_version' => 1,
            'operation' => 'commerce_runtime_rollback',
            'mode' => $mode,
            'status' => $status,
            'generated_at' => time(),
            'result' => $result,
        ];

        if ($error !== null) {
            $report['error'] = $error;
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $report
     */
    public static function write_atomic(string $path, array $report): void {
        $directory = dirname($path);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('Rollback report directory is not writable: ' . $directory);
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $temporary = tempnam($directory, basename($path) . '.tmp.');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create the temporary rollback report.');
        }

        try {
            if (file_put_contents($temporary, $json, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to write the temporary rollback report.');
            }
            if (!rename($temporary, $path)) {
                throw new \RuntimeException('Unable to replace the rollback report atomically.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
