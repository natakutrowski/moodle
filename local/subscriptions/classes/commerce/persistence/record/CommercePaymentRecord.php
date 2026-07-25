<?php

namespace local_subscriptions\commerce\persistence\record;

defined('MOODLE_INTERNAL') || die();

/** Immutable database-neutral record for one Commerce payment attempt. */
final class CommercePaymentRecord {
    public function __construct(
        private readonly string $purchaseuuid,
        private readonly int $sequence,
        private readonly ?string $provider,
        private readonly ?string $providerreference,
        private readonly string $status,
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $transactionid,
        private readonly ?int $legacyrequestid,
        private readonly ?int $paidat,
        private readonly string $metadatajson
    ) {
        if ($sequence < 0 || $amountminor < 0) {
            throw new \coding_exception('Invalid persisted Commerce payment sequence or amount.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('A persisted Commerce payment currency is invalid.');
        }
        if ($legacyrequestid !== null && $legacyrequestid <= 0) {
            throw new \coding_exception('A persisted Legacy payment request identifier must be positive.');
        }
    }

    public function get_purchase_uuid(): string { return $this->purchaseuuid; }
    public function get_sequence(): int { return $this->sequence; }

    public function to_record(): \stdClass {
        return (object)get_object_vars($this);
    }
}
