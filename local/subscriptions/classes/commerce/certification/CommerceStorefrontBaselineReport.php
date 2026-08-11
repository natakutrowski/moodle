<?php

namespace local_subscriptions\commerce\certification;

/**
 * Immutable result of the 7.95F7A storefront baseline audit.
 */
final class CommerceStorefrontBaselineReport {
    /** @var array<string, int|string|bool|array> */
    private array $inventory = [];

    /** @var array<int, array{severity:string, code:string, message:string, context:array}> */
    private array $issues = [];

    /** @var array<string, bool> */
    private array $checks = [];

    public function add_inventory(string $key, int|string|bool|array $value): void {
        $this->inventory[$key] = $value;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function add_issue(string $severity, string $code, string $message, array $context = []): void {
        $severity = strtolower(trim($severity));
        if (!in_array($severity, ['blocking', 'important', 'cosmetic'], true)) {
            throw new \InvalidArgumentException('Unsupported certification issue severity: ' . $severity);
        }

        $this->issues[] = [
            'severity' => $severity,
            'code' => trim($code),
            'message' => trim($message),
            'context' => $context,
        ];
    }

    public function add_check(string $key, bool $passed): void {
        $this->checks[$key] = $passed;
    }

    /** @return array<string, int|string|bool|array> */
    public function get_inventory(): array {
        return $this->inventory;
    }

    /** @return array<int, array{severity:string, code:string, message:string, context:array}> */
    public function get_issues(): array {
        return $this->issues;
    }

    /** @return array<string, bool> */
    public function get_checks(): array {
        return $this->checks;
    }

    public function count_issues(?string $severity = null): int {
        if ($severity === null) {
            return count($this->issues);
        }

        return count(array_filter(
            $this->issues,
            static fn(array $issue): bool => $issue['severity'] === $severity
        ));
    }

    public function has_blocking_issues(): bool {
        return $this->count_issues('blocking') > 0;
    }

    public function is_certifiable_baseline(): bool {
        return !$this->has_blocking_issues()
            && !in_array(false, $this->checks, true);
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'phase' => '7.95F7A',
            'generatedat' => time(),
            'certifiablebaseline' => $this->is_certifiable_baseline(),
            'summary' => [
                'blocking' => $this->count_issues('blocking'),
                'important' => $this->count_issues('important'),
                'cosmetic' => $this->count_issues('cosmetic'),
                'total' => $this->count_issues(),
            ],
            'checks' => $this->checks,
            'inventory' => $this->inventory,
            'issues' => $this->issues,
        ];
    }
}
