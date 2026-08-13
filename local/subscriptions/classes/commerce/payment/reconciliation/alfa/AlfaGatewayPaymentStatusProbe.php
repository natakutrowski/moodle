<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\alfa\AlfaGateway;

/** Alfa status probe backed by the configured production/test gateway. */
final class AlfaGatewayPaymentStatusProbe implements AlfaPaymentStatusProbeInterface {
    public function __construct(private readonly ?AlfaGateway $gateway = null) {
    }

    public function probe(string $orderid): AlfaPaymentProviderStatus {
        $orderid = trim($orderid);
        if ($orderid === '') {
            throw new \InvalidArgumentException('An Alfa order identifier is required for reconciliation.');
        }

        $gateway = $this->gateway ?? new AlfaGateway();
        // AlfaGateway::parse_webhook() always revalidates the order through
        // getOrderStatusExtended, so this is an authoritative live provider read.
        $event = $gateway->parse_webhook(
            json_encode(['orderId' => $orderid], JSON_UNESCAPED_UNICODE),
            []
        );
        $raw = is_array($event->meta['raw'] ?? null) ? $event->meta['raw'] : [];
        $paymentinfo = is_array($raw['paymentAmountInfo'] ?? null) ? $raw['paymentAmountInfo'] : [];

        return new AlfaPaymentProviderStatus(
            $orderid,
            isset($raw['orderStatus']) ? (int)$raw['orderStatus'] : null,
            isset($raw['amount']) ? (int)$raw['amount'] : null,
            self::normalize_currency($raw['currency'] ?? null),
            isset($paymentinfo['paymentState']) ? (string)$paymentinfo['paymentState'] : null,
            isset($paymentinfo['approvedAmount']) ? (int)$paymentinfo['approvedAmount'] : null,
            isset($paymentinfo['depositedAmount']) ? (int)$paymentinfo['depositedAmount'] : null,
            isset($paymentinfo['refundedAmount']) ? (int)$paymentinfo['refundedAmount'] : null,
            isset($raw['orderNumber']) ? (string)$raw['orderNumber'] : null,
            isset($raw['errorMessage']) ? (string)$raw['errorMessage'] : null,
            $raw,
            $event
        );
    }

    private static function normalize_currency(mixed $currency): ?string {
        if ($currency === null || $currency === '') {
            return null;
        }
        $value = strtoupper(trim((string)$currency));
        // Alfa/Sber-compatible API commonly returns ISO-4217 numeric 810 for RUB.
        return match ($value) {
            '810', 'RUB' => 'RUB',
            '978', 'EUR' => 'EUR',
            default => $value,
        };
    }
}
