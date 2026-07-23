<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;

/**
 * Result of one CRM Commerce shadow execution.
 */
final class CrmCommerceShadowResult {

    public function __construct(
        private readonly CrmCommerceCustomerSnapshot $snapshot,
        private readonly ?CrmCommerceSnapshotComparison $comparison,
        private readonly bool $fallbackused,
        private readonly ?string $commerceerror = null,
        private readonly ?string $legacyerror = null
    ) {
    }

    public function get_snapshot(): CrmCommerceCustomerSnapshot {
        return $this->snapshot;
    }

    public function get_comparison(): ?CrmCommerceSnapshotComparison {
        return $this->comparison;
    }

    public function was_fallback_used(): bool {
        return $this->fallbackused;
    }

    public function get_commerce_error(): ?string {
        return $this->commerceerror;
    }

    public function get_legacy_error(): ?string {
        return $this->legacyerror;
    }

    public function is_equivalent(): ?bool {
        return $this->comparison?->is_equivalent();
    }

    public function to_array(): array {
        return [
            'userid' => $this->snapshot->get_user_id(),
            'source' => $this->snapshot->get_source(),
            'fallbackused' => $this->fallbackused,
            'commerceerror' => $this->commerceerror,
            'legacyerror' => $this->legacyerror,
            'comparison' => $this->comparison?->to_array(),
        ];
    }
}