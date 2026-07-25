<?php

namespace local_subscriptions\commerce\persistence\record;

defined('MOODLE_INTERNAL') || die();

/** Immutable database-neutral record for one Commerce fulfillment operation. */
final class CommerceFulfillmentRecord {
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_SKIPPED,
        self::STATUS_FAILED,
    ];

    public function __construct(
        private readonly string $purchaseuuid,
        private readonly int $sequence,
        private readonly string $reference,
        private readonly string $fulfillmentkey,
        private readonly string $idempotencykey,
        private readonly string $status,
        private readonly string $metadatajson
    ) {
        if (
            $sequence < 0
            || trim($reference) === ''
            || trim($fulfillmentkey) === ''
            || trim($idempotencykey) === ''
            || !in_array($status, self::VALID_STATUSES, true)
        ) {
            throw new \coding_exception('Invalid persisted Commerce fulfillment record.');
        }
    }

    public function get_purchase_uuid(): string { return $this->purchaseuuid; }
    public function get_sequence(): int { return $this->sequence; }

    public function to_record(): \stdClass {
        return (object)get_object_vars($this);
    }
}
