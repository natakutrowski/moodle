<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a Commerce order.
 *
 * @deprecated since Phase 7.93I. Use CommercePurchase as the aggregate root.
 *
 * During Phase 7.93 this object is an in-memory domain representation only.
 * It is not backed by a new SQL table.
 */
final class CommerceOrder {

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    /**
     * @param string $reference Stable order reference.
     * @param int|null $userid Moodle user identifier, when known.
     * @param string|null $customeremail Customer email, including guest orders.
     * @param CommerceItem[] $items Items included in the order.
     * @param CommercePayment[] $payments Payments associated with the order.
     * @param string $status Current order status.
     * @param int|null $createdat Creation timestamp.
     * @param array $metadata Additional order information.
     */
    public function __construct(
        private readonly string $reference,
        private readonly ?int $userid,
        private readonly ?string $customeremail,
        private readonly array $items,
        private readonly array $payments = [],
        private readonly string $status = self::STATUS_DRAFT,
        private readonly ?int $createdat = null,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception('A Commerce order reference cannot be empty.');
        }

        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A Commerce order user identifier must be positive.');
        }

        if ($customeremail !== null && trim($customeremail) === '') {
            throw new \coding_exception('A Commerce order customer email cannot be empty.');
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \coding_exception('Unsupported Commerce order status: ' . $status);
        }

        foreach ($items as $item) {
            if (!$item instanceof CommerceItem) {
                throw new \coding_exception('Every Commerce order item must be a CommerceItem.');
            }
        }

        foreach ($payments as $payment) {
            if (!$payment instanceof CommercePayment) {
                throw new \coding_exception('Every Commerce order payment must be a CommercePayment.');
            }
        }
    }

    public function get_reference(): string {
        return $this->reference;
    }

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function get_customer_email(): ?string {
        return $this->customeremail;
    }

    /**
     * @return CommerceItem[]
     */
    public function get_items(): array {
        return $this->items;
    }

    /**
     * @return CommercePayment[]
     */
    public function get_payments(): array {
        return $this->payments;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function contains_multiple_items(): bool {
        return count($this->items) > 1;
    }

    public function is_paid(): bool {
        if (in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_COMPLETED,
        ], true)) {
            return true;
        }

        foreach ($this->payments as $payment) {
            if ($payment->is_successful()) {
                return true;
            }
        }

        return false;
    }
}