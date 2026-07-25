<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Immutable issue reported while migrating one Legacy Commerce purchase. */
final class CommerceLegacyMigrationIssue {
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    private const VALID_SEVERITIES = [
        self::SEVERITY_INFO,
        self::SEVERITY_WARNING,
        self::SEVERITY_ERROR,
    ];

    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly string $severity = self::SEVERITY_ERROR,
        private readonly array $context = []
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            throw new \coding_exception('Invalid Commerce migration issue code.');
        }
        if (trim($message) === '') {
            throw new \coding_exception('A Commerce migration issue message cannot be empty.');
        }
        if (!in_array($severity, self::VALID_SEVERITIES, true)) {
            throw new \coding_exception('Invalid Commerce migration issue severity.');
        }
    }

    public function get_code(): string { return $this->code; }
    public function get_message(): string { return $this->message; }
    public function get_severity(): string { return $this->severity; }
    public function get_context(): array { return $this->context; }
    public function is_error(): bool { return $this->severity === self::SEVERITY_ERROR; }

    public function to_array(): array {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'context' => $this->context,
        ];
    }
}
