<?php
declare(strict_types=1);

namespace local_subscriptions\payment;

use local_subscriptions\payment\dto\ProviderRefundResult;

defined('MOODLE_INTERNAL') || die();

interface RefundGatewayInterface {
    public function refund_payment(
        string $providerpaymentid,
        int $amountminor,
        string $currency,
        array $options = []
    ): ProviderRefundResult;
}
