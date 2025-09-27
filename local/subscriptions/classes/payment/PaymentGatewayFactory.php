<?php
namespace local_subscriptions\payment;

final class PaymentGatewayFactory {
    public static function for(string $provider): PaymentGatewayInterface {
        return match ($provider) {
            Provider::STRIPE => new stripe\StripeGateway(),
            Provider::ALFA   => new alfa\AlfaGateway(),
            default   => new stripe\StripeGateway(),
        };
    }
}
