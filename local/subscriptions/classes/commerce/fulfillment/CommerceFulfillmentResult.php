<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a fulfillment operation.
 */
final class CommerceFulfillmentResult {

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_SKIPPED,
        self::STATUS_FAILED,
    ];

    public function __construct(
        private readonly CommerceFulfillmentOperation $operation,
        private readonly string $status,
        private readonly ?string $message = null,
        private readonly array $metadata = []
    ) {
        if (
            !in_array(
                $status,
                self::VALID_STATUSES,
                true
            )
        ) {
            throw new \coding_exception(
                'Unsupported Commerce fulfillment result status: '
                . $status
            );
        }
    }

    public function get_operation():
        CommerceFulfillmentOperation {
        return $this->operation;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_message(): ?string {
        return $this->message;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_successful(): bool {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED,
                self::STATUS_SKIPPED,
            ],
            true
        );
    }
}