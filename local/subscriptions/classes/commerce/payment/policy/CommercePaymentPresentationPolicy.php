<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\policy;

use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;

defined('MOODLE_INTERNAL') || die();

/**
 * Admin-controlled customer-facing payment allow-list.
 *
 * Technical provider configuration/capabilities remain untouched. This policy
 * only constrains what the public checkout is allowed to consider/present.
 */
final class CommercePaymentPresentationPolicy {
    private const DEFAULT_PROVIDERS = [
        'stripe',
        'alfa',
        'paypal',
    ];

    /**
     * @return string[]
     */
    public function allowed_methods(): array {
        return $this->csv_config(
            'commerce_presented_payment_methods',
            CommercePaymentMethodCatalogue::keys()
        );
    }

    /**
     * @return string[]
     */
    public function allowed_providers(): array {
        return $this->csv_config(
            'commerce_presented_payment_providers',
            self::DEFAULT_PROVIDERS
        );
    }

    public function is_method_allowed(
        string $method
    ): bool {
        return in_array(
            strtolower(trim($method)),
            $this->allowed_methods(),
            true
        );
    }

    public function is_provider_allowed(
        string $provider
    ): bool {
        return in_array(
            strtolower(trim($provider)),
            $this->allowed_providers(),
            true
        );
    }

    /**
     * @param string[] $defaults
     * @return string[]
     */
    private function csv_config(
        string $key,
        array $defaults
    ): array {
        $configured =
            get_config(
                'local_subscriptions',
                $key
            );

        // Missing configuration preserves existing behaviour.
        if ($configured === false) {
            return array_values(
                array_unique(
                    array_map(
                        static fn(string $value): string =>
                            strtolower(trim($value)),
                        $defaults
                    )
                )
            );
        }

        // Empty value is meaningful: admin intentionally exposes none.
        if (trim((string)$configured) === '') {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(string $value): string =>
                            strtolower(trim($value)),
                        explode(
                            ',',
                            (string)$configured
                        )
                    ),
                    static fn(string $value): bool =>
                        $value !== ''
                )
            )
        );
    }
}
