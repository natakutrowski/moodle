<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregate result for one fulfillment batch.
 */
final class CommerceFulfillmentBatchResult {

    /**
     * @param CommerceFulfillmentResult[] $results
     */
    public function __construct(
        private readonly CommerceFulfillmentContext $context,
        private readonly array $results
    ) {
        foreach ($results as $result) {
            if (!$result instanceof CommerceFulfillmentResult) {
                throw new \coding_exception(
                    'A Commerce fulfillment batch contains an invalid result.'
                );
            }
        }
    }

    public function get_context(): CommerceFulfillmentContext {
        return $this->context;
    }

    /**
     * @return CommerceFulfillmentResult[]
     */
    public function get_results(): array {
        return $this->results;
    }

    public function is_successful(): bool {
        foreach ($this->results as $result) {
            if (!$result->is_successful()) {
                return false;
            }
        }

        return true;
    }

    public function has_failures(): bool {
        return !$this->is_successful();
    }

    public function count_completed(): int {
        return $this->count_status(
            CommerceFulfillmentResult::STATUS_COMPLETED
        );
    }

    public function count_skipped(): int {
        return $this->count_status(
            CommerceFulfillmentResult::STATUS_SKIPPED
        );
    }

    public function count_failed(): int {
        return $this->count_status(
            CommerceFulfillmentResult::STATUS_FAILED
        );
    }

    private function count_status(string $status): int {
        return count(array_filter(
            $this->results,
            static fn(CommerceFulfillmentResult $result): bool =>
                $result->get_status() === $status
        ));
    }
}
