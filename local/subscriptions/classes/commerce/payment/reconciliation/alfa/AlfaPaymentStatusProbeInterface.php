<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

/** Reads the authoritative provider-side status for one Alfa order. */
interface AlfaPaymentStatusProbeInterface {
    public function probe(string $orderid): AlfaPaymentProviderStatus;
}
