<?php

namespace local_subscriptions\validation;

defined('MOODLE_INTERNAL') || die();

/**
 * Résultat d’une validation technique.
 */
final class ValidationResult {

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $checks = [];

    /**
     * Ajoute un contrôle réussi.
     *
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    public function success(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->add(
            'ok',
            $code,
            $message,
            $context
        );
    }

    /**
     * Ajoute un avertissement.
     *
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    public function warning(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->add(
            'warning',
            $code,
            $message,
            $context
        );
    }

    /**
     * Ajoute une erreur.
     *
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    public function error(
        string $code,
        string $message,
        array $context = []
    ): void {
        $this->add(
            'error',
            $code,
            $message,
            $context
        );
    }

    /**
     * Ajoute un contrôle.
     *
     * @param string $severity
     * @param string $code
     * @param string $message
     * @param array<string,mixed> $context
     * @return void
     */
    private function add(
        string $severity,
        string $code,
        string $message,
        array $context
    ): void {
        $this->checks[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Retourne les contrôles.
     *
     * @return array<int,array<string,mixed>>
     */
    public function checks(): array {
        return $this->checks;
    }

    /**
     * Compte les contrôles par niveau.
     *
     * @param string $severity
     * @return int
     */
    public function count_by_severity(
        string $severity
    ): int {
        $count = 0;

        foreach ($this->checks as $check) {
            if (
                ($check['severity'] ?? '') ===
                $severity
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Indique si une erreur a été trouvée.
     *
     * @return bool
     */
    public function has_errors(): bool {
        return $this->count_by_severity(
            'error'
        ) > 0;
    }

    /**
     * Indique si un avertissement a été trouvé.
     *
     * @return bool
     */
    public function has_warnings(): bool {
        return $this->count_by_severity(
            'warning'
        ) > 0;
    }

    /**
     * Résumé.
     *
     * @return array<string,int>
     */
    public function summary(): array {
        return [
            'ok' =>
                $this->count_by_severity('ok'),

            'warnings' =>
                $this->count_by_severity('warning'),

            'errors' =>
                $this->count_by_severity('error'),

            'total' =>
                count($this->checks),
        ];
    }
}