<?php
namespace local_subscriptions\payment;

use stdClass;

interface PaymentGatewayInterface {
    public function create_checkout_session(stdClass $payment_request, array $options = []): dto\CheckoutInitResult;
    public function parse_webhook(string $payload, array $headers): dto\InternalEvent;
    public function cancel_subscription(string $provider_subscription_id, array $opts = []): dto\ProviderActionResult;
    public function resume_subscription(string $provider_subscription_id, array $opts = []): dto\ProviderActionResult;
    public function upgrade_subscription(string $provider_subscription_id, array $opts): dto\ProviderActionResult;
    public function get_customer_portal_url(?string $provider_customer_id, array $opts = []): ?string;
    public function capabilities(): dto\ProviderCapabilities;
}
