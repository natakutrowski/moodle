<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Global report for CRM Commerce shadow executions.
 */
final class CrmCommerceShadowAuditReport {

    private int $userschecked = 0;
    private int $equivalentusers = 0;
    private int $differentusers = 0;
    private int $fallbacks = 0;
    private int $failures = 0;

    /** @var array<int,array<string,mixed>> */
    private array $details = [];

    public function add_result(
        CrmCommerceShadowResult $result
    ): void {
        $this->userschecked++;

        if ($result->was_fallback_used()) {
            $this->fallbacks++;
        }

        $comparison = $result->get_comparison();

        if ($comparison === null) {
            if (
                $result->get_commerce_error() !== null
                || $result->get_legacy_error() !== null
            ) {
                $this->failures++;
            }
        } else if ($comparison->is_equivalent()) {
            $this->equivalentusers++;
        } else {
            $this->differentusers++;
        }

        if (
            $result->was_fallback_used()
            || $result->get_commerce_error() !== null
            || $result->get_legacy_error() !== null
            || $comparison?->is_equivalent() === false
        ) {
            $this->details[] =
                $result->to_array();
        }
    }

    public function get_users_checked(): int {
        return $this->userschecked;
    }

    public function get_equivalent_users(): int {
        return $this->equivalentusers;
    }

    public function get_different_users(): int {
        return $this->differentusers;
    }

    public function get_fallback_count(): int {
        return $this->fallbacks;
    }

    public function get_failure_count(): int {
        return $this->failures;
    }

    public function has_problems(): bool {
        return $this->differentusers > 0
            || $this->fallbacks > 0
            || $this->failures > 0;
    }

    public function get_details(): array {
        return $this->details;
    }

    public function to_array(): array {
        return [
            'summary' => [
                'userschecked' => $this->userschecked,
                'equivalentusers' => $this->equivalentusers,
                'differentusers' => $this->differentusers,
                'fallbacks' => $this->fallbacks,
                'failures' => $this->failures,
            ],
            'details' => $this->details,
        ];
    }
}