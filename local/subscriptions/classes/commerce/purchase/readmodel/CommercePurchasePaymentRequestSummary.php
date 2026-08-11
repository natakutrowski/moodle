<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Read-only operational detail for a Legacy payment request linked to a Native payment. */
final class CommercePurchasePaymentRequestSummary {
    /**
     * @param array<string, mixed> $details Explicitly allow-listed operational fields.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $family,
        public readonly string $status,
        public readonly string $provider,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly ?string $sessionid,
        public readonly ?string $transactionid,
        public readonly int $createdat,
        public readonly int $updatedat,
        public readonly ?int $expiresat,
        public readonly int $attempts,
        public readonly ?int $lastattempt,
        public readonly ?string $lasterror,
        public readonly array $details = []
    ) {
    }
}
