<?php

namespace local_subscriptions\commerce\fulfillment\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only fulfillment compatibility report.
 */
final class CommerceFulfillmentShadowReport {

    public function __construct(
        private readonly string $purchasekey,
        private readonly string $operationkey,
        private readonly bool $fulfilled,
        private readonly array $metadata = [],
        private readonly array $issues = []
    ) {
    }

    public function get_purchase_key(): string {
        return $this->purchasekey;
    }

    public function get_operation_key(): string {
        return $this->operationkey;
    }

    public function is_fulfilled(): bool {
        return $this->fulfilled;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_issues(): array {
        return $this->issues;
    }

    public function is_compatible(): bool {
        return $this->issues === [];
    }

    public function to_array(): array {
        return [
            'purchase_key' => $this->purchasekey,
            'operation_key' => $this->operationkey,
            'fulfilled' => $this->fulfilled,
            'compatible' => $this->is_compatible(),
            'metadata' => $this->metadata,
            'issues' => $this->issues,
        ];
    }
}
