<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * One validation issue detected while preparing a Commerce purchase.
 */
final class CommercePurchaseValidationIssue {

    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';

    private const VALID_SEVERITIES = [
        self::SEVERITY_ERROR,
        self::SEVERITY_WARNING,
    ];

    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly string $severity =
            self::SEVERITY_ERROR,
        private readonly array $context = []
    ) {
        if (trim($code) === '') {
            throw new \coding_exception(
                'A Commerce purchase validation issue code cannot be empty.'
            );
        }

        if (trim($message) === '') {
            throw new \coding_exception(
                'A Commerce purchase validation issue message cannot be empty.'
            );
        }

        if (
            !in_array(
                $severity,
                self::VALID_SEVERITIES,
                true
            )
        ) {
            throw new \coding_exception(
                'Unsupported Commerce purchase validation severity: '
                . $severity
            );
        }
    }

    public function get_code(): string {
        return $this->code;
    }

    public function get_message(): string {
        return $this->message;
    }

    public function get_severity(): string {
        return $this->severity;
    }

    public function get_context(): array {
        return $this->context;
    }

    public function is_error(): bool {
        return $this->severity ===
            self::SEVERITY_ERROR;
    }

    public function is_warning(): bool {
        return $this->severity ===
            self::SEVERITY_WARNING;
    }

    public function to_array(): array {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'context' => $this->context,
        ];
    }
}