<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\Provider;

/**
 * Determines whether a Legacy checkout belongs to an enabled Commerce pilot.
 */
final class CommerceCheckoutEligibility {

    public function is_digital_stripe_eur(\stdClass $request, string $table): bool {
        return $this->matches($request, $table, product_manager::TABLE_PAYMENT_REQUEST, Provider::STRIPE, 'EUR');
    }

    public function is_subscription_stripe_eur(\stdClass $request, string $table): bool {
        return $this->matches($request, $table, LegacyPaymentRequestContext::TABLE_SUBSCRIPTION, Provider::STRIPE, 'EUR');
    }

    public function is_digital_alfa_rub(\stdClass $request, string $table): bool {
        return $this->matches($request, $table, product_manager::TABLE_PAYMENT_REQUEST, Provider::ALFA, 'RUB');
    }

    public function is_subscription_alfa_rub(\stdClass $request, string $table): bool {
        return $this->matches($request, $table, LegacyPaymentRequestContext::TABLE_SUBSCRIPTION, Provider::ALFA, 'RUB');
    }

    private function matches(
        \stdClass $request,
        string $actualtable,
        string $expectedtable,
        string $expectedprovider,
        string $expectedcurrency
    ): bool {
        return strtolower(trim($actualtable)) === strtolower($expectedtable)
            && strtolower(trim((string)($request->payment_provider ?? ''))) === strtolower($expectedprovider)
            && strtoupper(trim((string)($request->currency ?? ''))) === strtoupper($expectedcurrency);
    }
}
