<?php

namespace local_subscriptions\payment\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Résultat de l’audit de cohérence des paiements.
 */
final class PaymentConsistencyReport {

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $issues = [];

    /**
     * @var array<string,int>
     */
    private array $counters = [];

    /**
     * Ajoute un compteur.
     *
     * @param string $key
     * @param int $value
     * @return void
     */
    public function set_counter(
        string $key,
        int $value
    ): void {
        $this->counters[$key] = $value;
    }

    /**
     * Ajoute une anomalie.
     *
     * @param string $severity
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    public function add_issue(
        string $severity,
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * @return array<string,int>
     */
    public function counters(): array {
        return $this->counters;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function issues(): array {
        return $this->issues;
    }

    /**
     * @return int
     */
    public function issue_count(): int {
        return count($this->issues);
    }

    /**
     * @param string $severity
     * @return int
     */
    public function count_by_severity(
        string $severity
    ): int {
        $count = 0;

        foreach ($this->issues as $issue) {
            if (
                ($issue['severity'] ?? '') ===
                $severity
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Indique si le rapport contient une erreur bloquante.
     *
     * @return bool
     */
    public function has_errors(): bool {
        return $this->count_by_severity(
            'error'
        ) > 0;
    }

    /**
     * Indique si le rapport contient au moins une anomalie.
     *
     * @return bool
     */
    public function has_issues(): bool {
        return !empty($this->issues);
    }
}