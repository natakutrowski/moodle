<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;

final class InboxAiResult {

    public function __construct(
        public readonly string $status,
        public readonly string $capability,
        public readonly string $provider,
        public readonly ?string $model,
        public readonly array $data,
        public readonly float $confidence,
        public readonly array $warnings = [],
        public readonly ?string $error = null,
        public readonly ?int $generatedat = null,
        public readonly array $metadata = []
    ) {
    }

    public static function unavailable(
        string $capability,
        string $provider,
        string $reason
    ): self {
        return new self(
            InboxAiStatus::UNAVAILABLE,
            $capability,
            $provider,
            null,
            [],
            0.0,
            [],
            $reason,
            time()
        );
    }

    public static function blocked(
        string $capability,
        string $reason,
        array $warnings = []
    ): self {
        return new self(
            InboxAiStatus::BLOCKED,
            $capability,
            'none',
            null,
            [],
            0.0,
            $warnings,
            $reason,
            time()
        );
    }

    public static function failed(
        string $capability,
        string $provider,
        string $error,
        array $metadata = []
    ): self {
        return new self(
            InboxAiStatus::FAILED,
            $capability,
            $provider,
            null,
            [],
            0.0,
            [],
            $error,
            time(),
            $metadata
        );
    }

    public function succeeded(): bool {
        return in_array(
            $this->status,
            [
                InboxAiStatus::SUCCESS,
                InboxAiStatus::PARTIAL,
            ],
            true
        );
    }

    public function with_warnings(
        array $warnings
    ): self {
        return new self(
            $this->status,
            $this->capability,
            $this->provider,
            $this->model,
            $this->data,
            $this->confidence,
            array_values(
                array_unique(
                    array_merge(
                        $this->warnings,
                        $warnings
                    )
                )
            ),
            $this->error,
            $this->generatedat,
            $this->metadata
        );
    }
}