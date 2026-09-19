<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\result\CommercePaymentStatus;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\EventRouter;
use local_subscriptions\payment\Provider;

/** Secure browser-return capture/finalization for PayPal Orders v2. */
final class PayPalReturnCaptureService {
    public function __construct(
        private readonly CommercePaymentRepository $payments,
        private readonly PayPalCommercePaymentProvider $provider
    ) {}

    public static function create(\moodle_database $database): self {
        $gatewayconfiguration = new PayPalGatewayConfiguration();
        return new self(
            new CommercePaymentRepository($database),
            new PayPalCommercePaymentProvider(
                new PayPalRestPaymentGateway($gatewayconfiguration),
                new PayPalPaymentProviderConfiguration(true)
            )
        );
    }

    public function capture(int $paymentid, string $returnedorderid): void {
        $attempt = $this->payments->find($paymentid);
        if ($attempt === null || $attempt->get_provider() !== Provider::PAYPAL) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        $orderid = trim((string)($attempt->get_provider_reference() ?? ''));
        if ($orderid === '' || !hash_equals($orderid, trim($returnedorderid))) {
            throw new \moodle_exception('invalidpaymentid', 'local_subscriptions');
        }

        if (in_array($attempt->get_status(), [
            CommercePaymentAttemptStatus::PAID,
            CommercePaymentAttemptStatus::COMPLETED,
        ], true)) {
            return;
        }

        $requestreference = $this->request_reference($attempt->get_provider_payload());
        $context = new \local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext(
            'commerce:paypal:return:' . $paymentid . ':' . hash('sha256', $orderid),
            Provider::env(Provider::PAYPAL) === 'live',
            ['commerce_payment_id' => $paymentid]
        );

        $before = $this->provider->retrieve($orderid, $context);
        $status = $before->get_metadata_value('paypal_order_status');
        if ($status !== 'APPROVED' && $status !== 'COMPLETED') {
            throw new \RuntimeException('PayPal order is not approved for capture.');
        }

        $result = $status === 'COMPLETED'
            ? $before
            : $this->provider->capture($orderid, $context, $requestreference);

        if ($result->get_status() !== CommercePaymentStatus::SUCCEEDED) {
            throw new \RuntimeException('PayPal did not confirm a completed capture.');
        }

        $order = $result->get_metadata_value('paypal_order');
        if (!is_array($order)) {
            throw new \RuntimeException('PayPal returned no verifiable order payload.');
        }

        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (!is_array($capture)) {
            throw new \RuntimeException('PayPal returned no completed capture payload.');
        }

        $capturestatus = strtoupper(trim((string)($capture['status'] ?? '')));
        $captureid = trim((string)($capture['id'] ?? ''));
        $amountvalue = (string)($capture['amount']['value'] ?? '');
        $currency = strtoupper(trim((string)($capture['amount']['currency_code'] ?? '')));

        if ($capturestatus !== 'COMPLETED' || $captureid === '') {
            throw new \RuntimeException('PayPal capture is not completed.');
        }

        $amount = \local_subscriptions\commerce\currency\CommerceCurrencyAmount::from_major_input(
            $amountvalue,
            $currency
        );
        if ($amount->get_amount_minor() !== $attempt->get_amount_minor()) {
            throw new \RuntimeException('PayPal capture amount does not match the Commerce payment.');
        }
        if ($currency !== $attempt->get_currency()) {
            throw new \RuntimeException('PayPal capture currency does not match the Commerce payment.');
        }

        $event = new InternalEvent('checkout_completed', [
            'currency' => $currency,
            'amount_minor' => $amount->get_amount_minor(),
            'meta' => [
                'provider' => Provider::PAYPAL,
                'commerce_payment_id' => (string)$paymentid,
                'commerce_purchase_uuid' => $attempt->get_purchase_uuid(),
                'provider_payment_id' => $orderid,
                'session' => $orderid,
                'transaction_id' => $captureid,
                'paypal_order_id' => $orderid,
                'paypal_capture_id' => $captureid,
                'paypal_order_status' => strtoupper(trim((string)($order['status'] ?? ''))),
                'paypal_capture_status' => $capturestatus,
                'reconciliation_source' => 'paypal_browser_return',
            ],
        ]);

        EventRouter::handle($event);
    }

    private function request_reference(?array $payload): string {
        $reference = trim((string)($payload['request_reference'] ?? ''));
        return $reference !== '' ? $reference : 'paypal-return';
    }
}
