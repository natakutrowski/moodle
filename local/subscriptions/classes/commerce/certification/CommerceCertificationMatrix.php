<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/**
 * Central release-certification matrix for all migrated checkout scenarios.
 */
final class CommerceCertificationMatrix {

    /** @return CommerceCertificationScenario[] */
    public function scenarios(): array {
        $standard = [
            'checkout_initialization',
            'provider_confirmation',
            'payment_request_persistence',
            'crm_visibility',
            'fulfillment',
            'emails',
            'idempotency',
        ];

        return [
            new CommerceCertificationScenario('digital_stripe_eur', 'digital', 'stripe', 'EUR', 'commerce_checkout_digital_stripe_eur_enabled', 'payment', $standard),
            new CommerceCertificationScenario('subscription_stripe_eur', 'subscription', 'stripe', 'EUR', 'commerce_checkout_subscription_stripe_eur_enabled', 'payment', $standard),
            new CommerceCertificationScenario('subscription_stripe_eur_recurring', 'subscription', 'stripe', 'EUR', 'commerce_checkout_subscription_stripe_eur_enabled', 'subscription', $standard),
            new CommerceCertificationScenario('upgrade_stripe_eur', 'subscription_upgrade', 'stripe', 'EUR', 'commerce_checkout_subscription_stripe_eur_enabled', 'payment', $standard),
            new CommerceCertificationScenario('retry_stripe_eur', 'retry', 'stripe', 'EUR', 'commerce_checkout_subscription_stripe_eur_enabled', 'original', $standard),
            new CommerceCertificationScenario('digital_alfa_rub', 'digital', 'alfa', 'RUB', 'commerce_checkout_digital_alfa_rub_enabled', 'payment', $standard),
            new CommerceCertificationScenario('subscription_alfa_rub', 'subscription', 'alfa', 'RUB', 'commerce_checkout_subscription_alfa_rub_enabled', 'payment', $standard),
            new CommerceCertificationScenario('retry_alfa_rub', 'retry', 'alfa', 'RUB', 'commerce_checkout_subscription_alfa_rub_enabled', 'original', $standard),
        ];
    }

    public function to_array(): array {
        return array_map(
            static fn(CommerceCertificationScenario $scenario): array => $scenario->to_array(),
            $this->scenarios()
        );
    }
}
