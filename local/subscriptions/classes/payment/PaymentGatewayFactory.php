<?php
namespace local_subscriptions\payment;

final class PaymentGatewayFactory {
    public static function for(string $provider): PaymentGatewayInterface {
        return match ($provider) {
            'stripe'  => new stripe\StripeGateway(),
            'alfa'    => new alfa\AlfaGateway(),
            default   => new stripe\StripeGateway(),
        };
    }
}
