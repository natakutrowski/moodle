<?php
declare(strict_types=1);

namespace local_subscriptions\payment;

use local_subscriptions\payment\dto\ProviderRefundResult;

defined('MOODLE_INTERNAL') || die();

interface RefundHistoryGatewayInterface {
    /**
     * @return ProviderRefundResult[]
     */
    public function list_payment_refunds(
        string $providerpaymentid,
        string $currency
    ): array;
}
