<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable payment-attempt presentation for CRM consumers. */
final class CommerceCustomerPayment {
    public function __construct(
        public readonly int $id,
        public readonly int $purchaseid,
        public readonly int $sequence,
        public readonly string $status,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly ?string $provider,
        public readonly ?string $providerreference,
        public readonly ?string $transactionid,
        public readonly ?int $paidat,
        public readonly int $timecreated,
        public readonly int $timemodified
    ) {
        if ($id <= 0 || $purchaseid <= 0 || $amountminor < 0) {
            throw new \coding_exception('Invalid Commerce customer payment presentation.');
        }
    }

    public function is_successful(): bool {
        return in_array($this->status, ['paid', 'completed', 'captured', 'succeeded', 'success'], true);
    }

    public function is_failed(): bool {
        return in_array($this->status, ['failed', 'error', 'declined', 'rejected'], true);
    }

    public function is_pending(): bool {
        return in_array($this->status, ['pending', 'created', 'processing', 'authorized'], true);
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'purchaseid' => $this->purchaseid,
            'sequence' => $this->sequence,
            'status' => $this->status,
            'currency' => $this->currency,
            'amountminor' => $this->amountminor,
            'provider' => $this->provider,
            'providerreference' => $this->providerreference,
            'transactionid' => $this->transactionid,
            'paidat' => $this->paidat,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'successful' => $this->is_successful(),
            'failed' => $this->is_failed(),
            'pending' => $this->is_pending(),
        ];
    }
}
