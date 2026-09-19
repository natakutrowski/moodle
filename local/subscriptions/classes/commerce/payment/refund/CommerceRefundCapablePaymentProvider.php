<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;

defined('MOODLE_INTERNAL') || die();

/**
 * Optional capability contract implemented only by certified refund providers.
 *
 * It deliberately does not extend the base provider interface so existing
 * providers and test doubles remain backwards-compatible.
 */
interface CommerceRefundCapablePaymentProvider {
    public function refund(
        CommercePaymentRefundRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentRefundResult;
}
