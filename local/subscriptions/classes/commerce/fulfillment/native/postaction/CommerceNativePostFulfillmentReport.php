<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\postaction;

defined('MOODLE_INTERNAL') || die();

/** Aggregate report of secondary Native actions. */
final class CommerceNativePostFulfillmentReport {
    /** @param CommerceNativePostFulfillmentActionResult[] $results */
    public function __construct(private readonly array $results) {
        foreach ($this->results as $result) {
            if (!$result instanceof CommerceNativePostFulfillmentActionResult) {
                throw new \coding_exception('Invalid Native post-fulfillment report result.');
            }
        }
    }

    public function get_results(): array { return $this->results; }
    public function has_failures(): bool {
        foreach ($this->results as $result) {
            if (!$result->is_successful()) { return true; }
        }
        return false;
    }
}
