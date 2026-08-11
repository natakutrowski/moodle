<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\invoice;

defined('MOODLE_INTERNAL') || die();

/** Selects the legal invoice issuer for the order currency/provider. */
final class CommerceInvoiceProfileResolver {
    public function resolve(string $currency, ?string $provider): array {
        $currency = strtoupper(trim($currency));
        $provider = strtolower(trim((string)$provider));
        $profile = $currency === 'RUB' ? 'rub' : 'eur';
        $providerprofile = in_array($provider, ['alfa', 'alfabank', 'alfa-bank'], true) ? 'rub'
            : ($provider === 'stripe' ? 'eur' : null);
        $mismatch = $providerprofile !== null && $providerprofile !== $profile;
        return [
            'key' => $profile,
            'name' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_name')),
            'address' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_address')),
            'legal' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_legal')),
            'email' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_email')),
            'phone' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_phone')),
            'website' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_website')),
            'taxnotice' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_tax_notice')),
            'footer' => trim((string)get_config('local_subscriptions', 'invoice_' . $profile . '_footer')),
            'mismatch' => $mismatch,
        ];
    }
}
