<?php

namespace local_subscriptions\commerce\certification;

/**
 * Small immutable-friendly report builder for Commerce certification phases.
 */
final class CommerceCertificationReport {
    /** @var array<int, array<string, mixed>> */
    private array $issues = [];

    /** @var array<string, mixed> */
    private array $inventory = [];

    public function __construct(private readonly string $phase) {
    }

    public function add_inventory(string $key, mixed $value): void {
        $this->inventory[$key] = $value;
    }

    /** @param array<string, mixed> $context */
    public function add_issue(string $severity, string $code, string $message, array $context = []): void {
        $this->issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function is_certifiable(): bool {
        foreach ($this->issues as $issue) {
            if ($issue['severity'] === 'blocking') {
                return false;
            }
        }
        return true;
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        $summary = ['blocking' => 0, 'important' => 0, 'cosmetic' => 0, 'total' => count($this->issues)];
        foreach ($this->issues as $issue) {
            $severity = (string)$issue['severity'];
            if (array_key_exists($severity, $summary)) {
                $summary[$severity]++;
            }
        }

        return [
            'phase' => $this->phase,
            'generatedat' => time(),
            'certifiable' => $this->is_certifiable(),
            'summary' => $summary,
            'inventory' => $this->inventory,
            'issues' => $this->issues,
        ];
    }
}
