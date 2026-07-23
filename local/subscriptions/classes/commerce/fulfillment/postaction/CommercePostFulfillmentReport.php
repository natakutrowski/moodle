<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregate result of secondary post-fulfillment actions.
 */
final class CommercePostFulfillmentReport {

    /**
     * @param CommercePostFulfillmentActionResult[] $results
     */
    public function __construct(
        private readonly array $results
    ) {
        foreach ($results as $result) {
            if (!$result instanceof CommercePostFulfillmentActionResult) {
                throw new \coding_exception(
                    'Invalid Commerce post-fulfillment result.'
                );
            }
        }
    }

    public function get_results(): array {
        return $this->results;
    }

    public function has_failures(): bool {
        foreach ($this->results as $result) {
            if (!$result->is_successful()) {
                return true;
            }
        }

        return false;
    }
}
