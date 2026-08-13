<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

/** Read-only diagnosis comparing one Campus payment attempt with Alfa. */
final class AlfaPaymentReconciliationInspection {
    public function __construct(
        public readonly int $paymentid,
        public readonly int $purchaseid,
        public readonly string $purchasereference,
        public readonly string $purchaseuuid,
        public readonly string $campuspaymentstatus,
        public readonly string $campuspurchasestatus,
        public readonly int $campusamountminor,
        public readonly string $campuscurrency,
        public readonly string $providerorderid,
        public readonly AlfaPaymentProviderStatus $provider,
        public readonly bool $amountmatches,
        public readonly bool $currencymatches,
        public readonly bool $approvedamountmatches,
        public readonly bool $depositedamountmatches,
        public readonly bool $providerpaid,
        public readonly bool $reconcilable,
        public readonly bool $alreadycomplete,
        public readonly array $blockers
    ) {
    }
}
