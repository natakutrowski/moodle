<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Safe payment information exposed by the customer order read model. */
final class CommerceOrderPaymentPresentation {
    public function __construct(
        public readonly string $status,
        public readonly ?string $provider,
        public readonly ?string $providerreference,
        public readonly ?string $transactionid,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly ?int $paidat,
        public readonly ?string $requeststatus = null,
        public readonly ?int $requestedat = null,
        public readonly ?int $expiresat = null
    ) {
    }
}
