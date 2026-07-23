<?php

namespace local_subscriptions\commerce\fulfillment\bridge;

defined('MOODLE_INTERNAL') || die();

/**
 * Controls the progressive activation of the Commerce fulfillment bridge.
 */
final class CommerceFulfillmentFeatureToggle {

    public function __construct(
        private readonly ?bool $override = null
    ) {
    }

    public function is_enabled(): bool {
        if ($this->override !== null) {
            return $this->override;
        }

        return (bool)get_config(
            'local_subscriptions',
            'commerce_fulfillment_enabled'
        );
    }
}
