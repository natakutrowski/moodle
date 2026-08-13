<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\InternalEvent;

/** Immutable normalized snapshot of one live Alfa order status query. */
final class AlfaPaymentProviderStatus {
    public function __construct(
        public readonly string $orderid,
        public readonly ?int $orderstatus,
        public readonly ?int $amountminor,
        public readonly ?string $currency,
        public readonly ?string $paymentstate,
        public readonly ?int $approvedamountminor,
        public readonly ?int $depositedamountminor,
        public readonly ?int $refundedamountminor,
        public readonly ?string $ordernumber,
        public readonly ?string $errormessage,
        public readonly array $raw,
        public readonly InternalEvent $event
    ) {
    }

    public function is_paid(): bool {
        if ($this->orderstatus !== 2) {
            return false;
        }
        // Older Alfa responses may omit paymentAmountInfo. If present, require
        // the actual deposited state rather than trusting an intermediate state.
        return $this->paymentstate === null || strtoupper($this->paymentstate) === 'DEPOSITED';
    }
}
