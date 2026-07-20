<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Result returned by one administrative tool execution.
 */
final class AdminToolExecutionResult {

    private function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly array $details = []
    ) {
        if (
            !AdminToolStatuses::is_valid(
                $this->status
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid administrative tool status.'
            );
        }
    }

    public static function success(
        string $message,
        array $details = []
    ): self {
        return new self(
            AdminToolStatuses::SUCCESS,
            $message,
            $details
        );
    }

    public static function failed(
        string $message,
        array $details = []
    ): self {
        return new self(
            AdminToolStatuses::FAILED,
            $message,
            $details
        );
    }

    public static function busy(
        string $message
    ): self {
        return new self(
            AdminToolStatuses::BUSY,
            $message
        );
    }

    public function is_success(): bool {
        return $this->status ===
            AdminToolStatuses::SUCCESS;
    }

    public function to_array(): array {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}