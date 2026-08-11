<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchasePaymentSummary {
    public function __construct(
        public readonly string $status,
        public readonly ?string $provider,
        public readonly ?string $providerreference,
        public readonly ?string $transactionid,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly ?int $paidat,
        public readonly ?CommercePurchasePaymentRequestSummary $paymentrequest = null
    ) {
    }
}
