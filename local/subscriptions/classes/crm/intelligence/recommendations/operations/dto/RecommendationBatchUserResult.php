<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Sanitized result of processing one CRM user.
 */
final class RecommendationBatchUserResult {

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly int $userid,
        public readonly string $status,
        public readonly int $generatedcount = 0,
        public readonly int $persistedcount = 0,
        public readonly int $duplicatecount = 0,
        public readonly int $correlationcount = 0,
        public readonly ?string $reason = null,
        public readonly ?string $exceptionclass = null,
        public readonly int $durationms = 0
    ) {
        if ($this->userid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation batch user ID must be greater than zero.'
            );
        }

        if (
            !in_array(
                $this->status,
                [
                    self::STATUS_SUCCESS,
                    self::STATUS_FAILED,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid recommendation batch user status.'
            );
        }

        if (
            $this->generatedcount < 0 ||
            $this->persistedcount < 0 ||
            $this->duplicatecount < 0 ||
            $this->correlationcount < 0 ||
            $this->durationms < 0
        ) {
            throw new \InvalidArgumentException(
                'Recommendation batch user counters cannot be negative.'
            );
        }
    }

    public function is_success(): bool {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function to_array(): array {
        return [
            'userid' => $this->userid,
            'status' => $this->status,
            'generatedcount' =>
                $this->generatedcount,
            'persistedcount' =>
                $this->persistedcount,
            'duplicatecount' =>
                $this->duplicatecount,
            'correlationcount' =>
                $this->correlationcount,
            'reason' => $this->reason,
            'exceptionclass' =>
                $this->exceptionclass,
            'durationms' => $this->durationms,
        ];
    }
}