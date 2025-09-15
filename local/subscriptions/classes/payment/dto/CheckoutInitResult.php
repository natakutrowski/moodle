<?php
namespace local_subscriptions\payment\dto;

final class CheckoutInitResult {
    public string $redirect_url;
    public ?string $provider_session_id = null;
    public function __construct(string $url, ?string $sid=null) {
        $this->redirect_url = $url; $this->provider_session_id = $sid;
    }
}
