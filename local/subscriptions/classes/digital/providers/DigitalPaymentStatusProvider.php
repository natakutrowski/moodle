<?php

namespace local_subscriptions\digital\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\dto\DigitalPaymentProviderStatus;

/**
 * Checks the live status of a digital payment request.
 */
interface DigitalPaymentStatusProvider {

    public function provider_key(): string;

    public function supports(
        string $provider
    ): bool;

    public function check(
        \stdClass $paymentrequest
    ): DigitalPaymentProviderStatus;
}