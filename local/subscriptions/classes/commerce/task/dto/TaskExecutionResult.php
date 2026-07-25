<?php

namespace local_subscriptions\commerce\task\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable-at-the-boundary execution report shared by all Commerce cron jobs.
 */
final class TaskExecutionResult {

    private float $startedat;
    private ?float $finishedat = null;
    private int $memorystart;
    private int $queriesstart;
    private array $counters = [];
    private array $warnings = [];
    private array $errors = [];
    private bool $locked = false;

    public function __construct(
        private readonly string $jobname,
    ) {
        global $DB;

        $this->startedat = microtime(true);
        $this->memorystart = memory_get_usage(true);
        $this->queriesstart = method_exists($DB, 'perf_get_queries')
            ? (int) $DB->perf_get_queries()
            : 0;
    }

    public function increment(string $name, int $amount = 1): void {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $amount;
    }

    public function add_warning(int|string $reference, string $message): void {
        $this->warnings[] = [
            'reference' => (string) $reference,
            'message' => $message,
        ];
        $this->increment('warnings');
    }

    public function add_error(int|string $reference, \Throwable $exception): void {
        $this->errors[] = [
            'reference' => (string) $reference,
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ];
        $this->increment('failed');
    }

    public function mark_locked(): void {
        $this->locked = true;
        $this->increment('locked');
    }

    public function finish(): self {
        $this->finishedat ??= microtime(true);
        return $this;
    }

    public function job_name(): string {
        return $this->jobname;
    }

    public function counters(): array {
        return $this->counters;
    }

    public function warnings(): array {
        return $this->warnings;
    }

    public function errors(): array {
        return $this->errors;
    }

    public function locked(): bool {
        return $this->locked;
    }

    public function succeeded(): bool {
        return !$this->locked && empty($this->errors);
    }

    public function status(): string {
        if ($this->locked) {
            return 'locked';
        }

        if (!empty($this->errors)) {
            return 'failed';
        }

        if (!empty($this->warnings)) {
            return 'warning';
        }

        return 'success';
    }

    public function started_at(): int {
        return (int) floor($this->startedat);
    }

    public function finished_at(): int {
        return (int) floor($this->finishedat ?? microtime(true));
    }

    public function duration_ms(): int {
        $end = $this->finishedat ?? microtime(true);
        return max(0, (int) round(($end - $this->startedat) * 1000));
    }

    public function peak_memory_bytes(): int {
        return max(0, memory_get_peak_usage(true) - $this->memorystart);
    }

    public function db_queries(): int {
        global $DB;

        if (!method_exists($DB, 'perf_get_queries')) {
            return 0;
        }

        return max(0, (int) $DB->perf_get_queries() - $this->queriesstart);
    }
}
