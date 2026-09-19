<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\orchestration;

use local_subscriptions\commerce\payment\CommercePaymentRequest;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable provider/method provenance attached to payment orchestration.
 */
final class CommercePaymentProvenance {
    public function __construct(
        private readonly string $provider,
        private readonly ?string $method,
        private readonly string $currency,
        private readonly string $country
    ) {
    }

    public static function from_request(
        CommercePaymentRequest $request,
        string $provider
    ): self {
        return new self(
            strtolower(trim($provider)),
            $request->get_preferred_payment_method(),
            $request->get_currency(),
            strtoupper(trim((string)$request->get_metadata_value(
                'payment_country',
                'ZZ'
            )))
        );
    }

    /**
     * @return array{provider:string,paymentmethod:?string,currency:string,country:string}
     */
    public function to_array(): array {
        return [
            'provider' => $this->provider,
            'paymentmethod' => $this->method,
            'currency' => strtoupper($this->currency),
            'country' => preg_match('/^[A-Z]{2}$/', $this->country)
                ? $this->country
                : 'ZZ',
        ];
    }
}
