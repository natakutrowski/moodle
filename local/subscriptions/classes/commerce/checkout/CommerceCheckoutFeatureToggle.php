<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/**
 * Feature toggles controlling the progressive Commerce checkout migration.
 */
final class CommerceCheckoutFeatureToggle {

    public function is_shadow_enabled(): bool {
        return $this->enabled('commerce_checkout_shadow_enabled');
    }

    public function is_digital_stripe_eur_enabled(): bool {
        return $this->enabled('commerce_checkout_digital_stripe_eur_enabled');
    }

    public function is_subscription_stripe_eur_enabled(): bool {
        return $this->enabled('commerce_checkout_subscription_stripe_eur_enabled');
    }

    public function is_digital_alfa_rub_enabled(): bool {
        return $this->enabled('commerce_checkout_digital_alfa_rub_enabled');
    }

    public function is_subscription_alfa_rub_enabled(): bool {
        return $this->enabled('commerce_checkout_subscription_alfa_rub_enabled');
    }

    private function enabled(string $name): bool {
        return !empty(get_config('local_subscriptions', $name));
    }
}
