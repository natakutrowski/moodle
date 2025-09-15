<?php
namespace local_subscriptions\payment\alfa;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\dto\{CheckoutInitResult, InternalEvent, ProviderActionResult, ProviderCapabilities};
use stdClass;

final class AlfaGateway implements PaymentGatewayInterface {

    private function cfg(): array {
        return [
            'base_url'      => get_config('local_subscriptions', 'alfa_base_url') ?? '',
            'merchant_id'   => get_config('local_subscriptions', 'alfa_merchant_id') ?? '',
            'secret'        => get_config('local_subscriptions', 'alfa_secret') ?? '',
            'return_url'    => (new \moodle_url('/local/subscriptions/return.php'))->out(false),
            'callback_url'  => (new \moodle_url('/local/subscriptions/webhook/alfa.php'))->out(false),
        ];
    }

    public function create_checkout_session(stdClass $payment_request, array $options = []): CheckoutInitResult {
        $mode = $options['mode'] ?? 'payment';
        $cfg = $this->cfg();
        $order = [
            'merchantId'   => $cfg['merchant_id'],
            'orderId'      => (string)$payment_request->id,
            'amount'       => (int)round($payment_request->price * 100),
            'currency'     => $payment_request->currency,
            'description'  => 'CampusFR subscription',
            'returnUrl'    => $cfg['return_url'],
            'callbackUrl'  => $cfg['callback_url'],
        ];
        $payUrl = $cfg['base_url'].'/demo/pay?orderId='.$order['orderId'];
        $providerSession = $order['orderId'];
        return new CheckoutInitResult($payUrl, $providerSession);
    }

    public function parse_webhook(string $payload, array $headers): InternalEvent {
        $data = json_decode($payload, true) ?: [];
        $status = $data['status'] ?? 'UNKNOWN';
        $paymentRequestId = $data['orderId'] ?? null;
        $amountMinor = isset($data['amount']) ? (int)$data['amount'] : null;
        $currency = $data['currency'] ?? null;

        switch ($status) {
            case 'PAID':
                return new InternalEvent('checkout_completed', [
                    'payment_request_id' => $paymentRequestId,
                    'amount_minor'       => $amountMinor,
                    'currency'           => $currency,
                    'meta' => ['provider'=>'alfa','raw'=>$data],
                ]);
            case 'RECURRING_PAID':
                return new InternalEvent('invoice_paid', [
                    'payment_request_id' => $paymentRequestId,
                    'amount_minor'       => $amountMinor,
                    'currency'           => $currency,
                    'meta' => ['provider'=>'alfa','raw'=>$data],
                ]);
            case 'FAILED':
            case 'DECLINED':
                return new InternalEvent('invoice_failed', [
                    'payment_request_id' => $paymentRequestId,
                    'meta' => ['provider'=>'alfa','raw'=>$data],
                ]);
            case 'SUBSCRIPTION_CANCELED':
                return new InternalEvent('subscription_canceled', [
                    'provider_subscription_id' => $data['subscriptionId'] ?? null,
                    'meta' => ['provider'=>'alfa','raw'=>$data],
                ]);
            default:
                return new InternalEvent('subscription_updated', [
                    'meta' => ['provider'=>'alfa','raw'=>$data],
                ]);
        }
    }

    public function cancel_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function resume_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function upgrade_subscription(string $provider_subscription_id, array $opts): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function get_customer_portal_url(?string $provider_customer_id, array $opts = []): ?string {
        return null;
    }

    public function capabilities(): ProviderCapabilities {
        $c = new ProviderCapabilities();
        $c->supports_recurring = false;
        $c->supports_portal = false;
        $c->currencies = ['RUB'];
        return $c;
    }
}
