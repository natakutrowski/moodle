<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Complete, immutable Native purchase presentation for CRM consumers. */
final class CommerceCustomerPurchase {
    /**
     * @param array<int, array<string, mixed>> $items
     * @param CommerceCustomerPayment[] $payments
     * @param CommerceCustomerGrant[] $grants
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $reference,
        public readonly string $publicreference,
        public readonly string $type,
        public readonly string $status,
        public readonly string $currency,
        public readonly int $totalminor,
        public readonly ?int $userid,
        public readonly ?string $customeremail,
        public readonly int $timecreated,
        public readonly int $timemodified,
        public readonly array $items,
        public readonly array $payments,
        public readonly array $grants,
        public readonly array $metadata = [],
        public readonly bool $guestorigin = false
    ) {
        if ($id <= 0 || trim($reference) === '' || $totalminor < 0) {
            throw new \coding_exception('Invalid Commerce customer purchase presentation.');
        }
        foreach ($payments as $payment) {
            if (!$payment instanceof CommerceCustomerPayment) {
                throw new \coding_exception('A Commerce customer purchase requires payment presentations.');
            }
        }
        foreach ($grants as $grant) {
            if (!$grant instanceof CommerceCustomerGrant) {
                throw new \coding_exception('A Commerce customer purchase requires Grant presentations.');
            }
        }
    }

    public function has_successful_payment(): bool {
        foreach ($this->payments as $payment) {
            if ($payment->is_successful()) {
                return true;
            }
        }
        return false;
    }

    public function has_failed_payment(): bool {
        foreach ($this->payments as $payment) {
            if ($payment->is_failed()) {
                return true;
            }
        }
        return false;
    }

    public function successful_paid_at(): ?int {
        $timestamps = [];
        foreach ($this->payments as $payment) {
            if ($payment->is_successful()) {
                $timestamps[] = $payment->paidat ?? $payment->timemodified;
            }
        }
        return $timestamps === [] ? null : max($timestamps);
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference' => $this->reference,
            'publicreference' => $this->publicreference,
            'type' => $this->type,
            'status' => $this->status,
            'currency' => $this->currency,
            'totalminor' => $this->totalminor,
            'userid' => $this->userid,
            'customeremail' => $this->customeremail,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'items' => $this->items,
            'payments' => array_map(static fn(CommerceCustomerPayment $payment): array => $payment->to_array(), $this->payments),
            'grants' => array_map(static fn(CommerceCustomerGrant $grant): array => $grant->to_array(), $this->grants),
            'successfulpayment' => $this->has_successful_payment(),
            'failedpayment' => $this->has_failed_payment(),
            'successfulpaidat' => $this->successful_paid_at(),
            'metadata' => $this->metadata,
            'guestorigin' => $this->guestorigin,
        ];
    }
}
