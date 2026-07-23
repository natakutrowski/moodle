<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/**
 * Operational switch controlling the Commerce checkout engine.
 *
 * Scenario-level migration toggles were removed after Commerce certification.
 * Eligibility rules still determine which provider/currency combinations are
 * supported by the Commerce checkout.
 */
final class CommerceCheckoutFeatureToggle {

    public function __construct(
        private readonly ?bool $override = null
    ) {
    }

    public function is_enabled(): bool {
        if ($this->override !== null) {
            return $this->override;
        }

        return !empty(get_config(
            'local_subscriptions',
            'commerce_checkout_enabled'
        ));
    }
}