<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\batch;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Aggregate result for all Native grants belonging to one purchase. */
final class CommerceNativeFulfillmentBatchResult {
    /** @param CommerceNativeFulfillmentResult[] $results */
    public function __construct(
        private readonly string $purchasereference,
        private readonly CommerceNativeFulfillmentContext $context,
        private readonly array $results
    ) {
        if (trim($this->purchasereference) === '') {
            throw new \coding_exception('A Native fulfillment batch requires a purchase reference.');
        }
        foreach ($this->results as $result) {
            if (!$result instanceof CommerceNativeFulfillmentResult) {
                throw new \coding_exception('Invalid Native fulfillment batch result.');
            }
        }
    }

    public function get_purchase_reference(): string {
        return $this->purchasereference;
    }

    public function get_context(): CommerceNativeFulfillmentContext {
        return $this->context;
    }

    /** @return CommerceNativeFulfillmentResult[] */
    public function get_results(): array {
        return $this->results;
    }

    public function count(): int {
        return count($this->results);
    }

    public function completed_count(): int {
        return count(array_filter($this->results, static fn(CommerceNativeFulfillmentResult $result): bool => $result->is_completed()));
    }

    public function skipped_count(): int {
        return count(array_filter($this->results, static fn(CommerceNativeFulfillmentResult $result): bool => $result->is_skipped()));
    }

    public function failed_count(): int {
        return count(array_filter($this->results, static fn(CommerceNativeFulfillmentResult $result): bool => $result->is_failed()));
    }

    public function is_successful(): bool {
        return $this->failed_count() === 0;
    }

    public function to_array(): array {
        return [
            'purchasereference' => $this->purchasereference,
            'count' => $this->count(),
            'completed' => $this->completed_count(),
            'skipped' => $this->skipped_count(),
            'failed' => $this->failed_count(),
            'successful' => $this->is_successful(),
        ];
    }
}
