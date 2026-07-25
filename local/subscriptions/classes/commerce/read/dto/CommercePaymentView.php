<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\dto;

defined('MOODLE_INTERNAL') || die();

/** Immutable read model for one Commerce payment attempt. */
final class CommercePaymentView {
    public function __construct(
        public readonly int $sequence,
        public readonly ?string $provider,
        public readonly ?string $providerreference,
        public readonly string $status,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly ?string $transactionid,
        public readonly ?int $legacyrequestid,
        public readonly ?int $paidat,
        public readonly array $metadata = []
    ) {
    }
}
