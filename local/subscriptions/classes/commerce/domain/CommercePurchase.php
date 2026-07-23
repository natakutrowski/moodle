<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Base domain representation of a completed or attempted Commerce purchase.
 *
 * A CommercePurchase does not correspond to a SQL table. Specialised purchase
 * objects may be built from the existing subscription and digital tables.
 */
abstract class CommercePurchase {

    /**
     * @param string $reference Stable domain reference.
     * @param CommerceItem $item Item concerned by the purchase.
     * @param CommercePayment $payment Payment associated with the purchase.
     * @param int|null $userid Moodle user identifier, when known.
     * @param string|null $customeremail Customer email, including guest buyers.
     * @param string $status Purchase status.
     * @param int|null $createdat Purchase creation timestamp.
     * @param int|null $updatedat Last known update timestamp.
     * @param array $metadata Additional domain information.
     */
    public function __construct(
        private readonly string $reference,
        private readonly CommerceItem $item,
        private readonly CommercePayment $payment,
        private readonly ?int $userid,
        private readonly ?string $customeremail,
        private readonly string $status,
        private readonly ?int $createdat = null,
        private readonly ?int $updatedat = null,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception('A Commerce purchase reference cannot be empty.');
        }

        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A Commerce purchase user identifier must be positive.');
        }

        if ($customeremail !== null && trim($customeremail) === '') {
            throw new \coding_exception('A Commerce purchase customer email cannot be empty.');
        }

        if (trim($status) === '') {
            throw new \coding_exception('A Commerce purchase status cannot be empty.');
        }
    }

    abstract public function get_type(): string;

    public function get_reference(): string {
        return $this->reference;
    }

    public function get_item(): CommerceItem {
        return $this->item;
    }

    public function get_payment(): CommercePayment {
        return $this->payment;
    }

    public function get_user_id(): ?int {
        return $this->userid;
    }

    public function get_customer_email(): ?string {
        return $this->customeremail;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }

    public function get_updated_at(): ?int {
        return $this->updatedat;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function is_paid(): bool {
        return $this->payment->is_successful();
    }
}