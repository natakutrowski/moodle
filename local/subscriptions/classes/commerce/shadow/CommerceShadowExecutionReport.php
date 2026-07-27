<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Read-only report produced by one G2 Native Shadow execution. */
final class CommerceShadowExecutionReport {
    /** @param CommerceNativeFulfillmentResult[] $results @param CommerceShadowEffect[] $effects */
    public function __construct(
        private readonly string $purchasereference,
        private readonly string $source,
        private readonly string $executionreference,
        private readonly int $startedat,
        private readonly int $finishedat,
        private readonly array $results,
        private readonly array $effects,
        private readonly array $errors = []
    ) {
    }

    public function get_purchase_reference(): string { return $this->purchasereference; }
    public function get_source(): string { return $this->source; }
    public function get_execution_reference(): string { return $this->executionreference; }
    public function get_started_at(): int { return $this->startedat; }
    public function get_finished_at(): int { return $this->finishedat; }
    public function get_results(): array { return $this->results; }
    public function get_effects(): array { return $this->effects; }
    public function get_errors(): array { return $this->errors; }
    public function is_successful(): bool { return $this->errors === [] && $this->failed_count() === 0; }
    public function count(): int { return count($this->results); }
    public function failed_count(): int { return count(array_filter($this->results, static fn($result): bool => $result->is_failed())); }
    public function duration_ms(): int { return max(0, ($this->finishedat - $this->startedat) * 1000); }

    public function to_array(): array {
        return [
            'purchasereference' => $this->purchasereference,
            'source' => $this->source,
            'executionreference' => $this->executionreference,
            'count' => $this->count(),
            'failed' => $this->failed_count(),
            'effects' => count($this->effects),
            'errors' => $this->errors,
            'successful' => $this->is_successful(),
        ];
    }
}
