<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
interface StripePaymentStatusProbeInterface {
    public function probe(string $sessionid): StripePaymentProviderStatus;
}
