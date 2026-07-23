<?php

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents one compatibility problem detected between legacy data
 * and the new Commerce domain.
 */
final class CommerceCompatibilityIssue {

    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    private const VALID_SEVERITIES = [
        self::SEVERITY_WARNING,
        self::SEVERITY_ERROR,
    ];

    public function __construct(
        private readonly string $severity,
        private readonly string $code,
        private readonly string $message,
        private readonly array $context = []
    ) {
        if (!in_array($severity, self::VALID_SEVERITIES, true)) {
            throw new \coding_exception(
                'Unsupported Commerce audit severity: ' . $severity
            );
        }

        if (trim($code) === '') {
            throw new \coding_exception(
                'A Commerce audit issue code cannot be empty.'
            );
        }

        if (trim($message) === '') {
            throw new \coding_exception(
                'A Commerce audit issue message cannot be empty.'
            );
        }
    }

    public function get_severity(): string {
        return $this->severity;
    }

    public function get_code(): string {
        return $this->code;
    }

    public function get_message(): string {
        return $this->message;
    }

    public function get_context(): array {
        return $this->context;
    }

    public function to_array(): array {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}