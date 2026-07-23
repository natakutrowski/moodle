<?php

namespace local_subscriptions\commerce\fulfillment\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentBatchResult;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentReport;

/**
 * Result returned by the controlled post-payment bridge.
 */
final class CommercePostPaymentResult {

    public function __construct(
        private readonly bool $enabled,
        private readonly ?CommerceFulfillmentBatchResult $fulfillment = null,
        private readonly ?CommercePostFulfillmentReport $postactions = null
    ) {
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function get_fulfillment(): ?CommerceFulfillmentBatchResult {
        return $this->fulfillment;
    }

    public function get_post_actions(): ?CommercePostFulfillmentReport {
        return $this->postactions;
    }

    public function is_successful(): bool {
        return !$this->enabled
            || ($this->fulfillment !== null
                && $this->fulfillment->is_successful());
    }
}
