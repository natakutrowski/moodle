<?php

namespace local_subscriptions\commerce\purchase\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only shadow evaluation report for one Commerce purchase.
 */
final class CommercePurchaseShadowReport {

    /**
     * @param string $purchasekey Stable purchase key.
     * @param array $snapshot Normalised purchase snapshot.
     * @param array $issues Business validation issues.
     * @param array $errors Technical errors.
     */
    public function __construct(
        private readonly string $purchasekey,
        private readonly array $snapshot,
        private readonly array $issues = [],
        private readonly array $errors = []
    ) {
    }

    /**
     * Return the stable purchase key.
     *
     * @return string
     */
    public function get_purchase_key(): string {
        return $this->purchasekey;
    }

    /**
     * Return the mapped purchase snapshot.
     *
     * @return array
     */
    public function get_snapshot(): array {
        return $this->snapshot;
    }

    /**
     * Return business validation issues.
     *
     * @return array
     */
    public function get_issues(): array {
        return $this->issues;
    }

    /**
     * Return technical errors.
     *
     * @return array
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Whether the purchase is compatible with the Commerce domain.
     *
     * @return bool
     */
    public function is_compatible(): bool {
        return $this->issues === []
            && $this->errors === [];
    }

    /**
     * Export the report.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'purchase_key' => $this->get_purchase_key(),
            'compatible' => $this->is_compatible(),
            'snapshot' => $this->get_snapshot(),
            'issues' => $this->get_issues(),
            'errors' => $this->get_errors(),
        ];
    }
}