<?php
namespace local_subscriptions\payment\dto;

final class InternalEvent {
    public string $type;
    public ?string $payment_request_id = null;
    public ?string $provider_subscription_id = null;
    public ?string $provider_customer_id = null;
    public ?string $currency = null;
    public ?int $amount_minor = null;
    public array $meta = [];

    public function __construct(string $type, array $data=[]) {
        $this->type = $type;
        foreach ($data as $k=>$v) { $this->$k = $v; }
    }
}
