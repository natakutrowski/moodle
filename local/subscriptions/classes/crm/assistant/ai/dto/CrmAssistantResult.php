<?php

namespace local_subscriptions\crm\assistant\ai\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Complete result of one conversational CRM Assistant execution.
 */
final class CrmAssistantResult {

    public const STATUS_SUCCESS = 'success';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?CrmAssistantAnswer $answer = null,
        public readonly ?string $reason = null,
        public readonly array $metadata = []
    ) {
        if (
            !in_array(
                $this->status,
                self::statuses(),
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant result status.'
            );
        }

        if (
            $this->status === self::STATUS_SUCCESS &&
            $this->answer === null
        ) {
            throw new \InvalidArgumentException(
                'Successful CRM Assistant result requires an answer.'
            );
        }

        if (
            $this->status !== self::STATUS_SUCCESS &&
            $this->reason === null
        ) {
            throw new \InvalidArgumentException(
                'Unsuccessful CRM Assistant result requires a reason.'
            );
        }
    }

    /**
     * @return string[]
     */
    public static function statuses(): array {
        return [
            self::STATUS_SUCCESS,
            self::STATUS_UNAVAILABLE,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ];
    }

    public static function success(
        CrmAssistantAnswer $answer,
        array $metadata = []
    ): self {
        return new self(
            self::STATUS_SUCCESS,
            $answer,
            null,
            $metadata
        );
    }

    public static function unavailable(
        string $reason
    ): self {
        return new self(
            self::STATUS_UNAVAILABLE,
            null,
            $reason
        );
    }

    public static function rejected(
        string $reason
    ): self {
        return new self(
            self::STATUS_REJECTED,
            null,
            $reason
        );
    }

    public static function failed(
        string $reason,
        array $metadata = []
    ): self {
        return new self(
            self::STATUS_FAILED,
            null,
            $reason,
            $metadata
        );
    }

    public function is_success(): bool {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function to_object(): \stdClass {
        return (object)[
            'status' => $this->status,
            'reason' => $this->reason,
            'answer' => $this->answer?->to_object(),
            'metadata' => $this->metadata,
        ];
    }
}