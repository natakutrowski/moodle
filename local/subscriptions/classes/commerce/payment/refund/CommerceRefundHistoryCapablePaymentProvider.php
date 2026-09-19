<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;

defined('MOODLE_INTERNAL') || die();

interface CommerceRefundHistoryCapablePaymentProvider {
    /**
     * @return CommercePaymentRefundResult[]
     */
    public function list_refunds(
        string $providerpaymentid,
        string $currency,
        CommercePaymentProviderContext $context
    ): array;
}
