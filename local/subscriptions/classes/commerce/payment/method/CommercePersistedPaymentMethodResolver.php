<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\method;

defined('MOODLE_INTERNAL') || die();

final class CommercePersistedPaymentMethodResolver {
    public function resolve(
        ?string $provider,
        array $metadata = [],
        ?array $providerpayload = null
    ): ?string {
        foreach ([
            $metadata['payment_method'] ?? null,
            $metadata['commerce_payment_method'] ?? null,
            $providerpayload['result_metadata']['commerce_payment_method'] ?? null,
            $providerpayload['result_metadata']['payment_method'] ?? null,
            $providerpayload['action']['metadata']['payment_method'] ?? null,
        ] as $candidate) {
            $method = $this->normalise_candidate($candidate);
            if ($method !== null) {
                return $method;
            }
        }

        $provider = strtolower(trim((string)$provider));
        if ($provider === 'paypal') {
            return CommercePaymentMethod::PAYPAL;
        }

        return null;
    }

    public function label(?string $method): string {
        $method = $this->normalise_candidate($method);
        if ($method === null) {
            return get_string('commerce_payment_method_unknown', 'local_subscriptions');
        }

        $key = 'commerce_payment_method_' . $method;
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : $method;
    }

    private function normalise_candidate(mixed $candidate): ?string {
        if (!is_scalar($candidate)) {
            return null;
        }
        $method = strtolower(trim((string)$candidate));
        $aliases = [
            'alfapay' => CommercePaymentMethod::ALFA_PAY,
            'mirpay' => CommercePaymentMethod::MIR_PAY,
            'googlepay' => CommercePaymentMethod::GOOGLE_PAY,
            'applepay' => CommercePaymentMethod::APPLE_PAY,
        ];
        $method = $aliases[$method] ?? $method;

        if ($method === '' || !in_array($method, CommercePaymentMethod::KNOWN, true)) {
            return null;
        }
        return $method;
    }
}
