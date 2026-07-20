<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

/**
 * Current and previous Dashboard CRM trends.
 */
final class DashboardTrendsComparison {

    /**
     * @param DashboardTrendsSnapshot $current
     * @param DashboardTrendsSnapshot $previous
     * @param DashboardTrendComparison[] $metrics
     */
    public function __construct(
        public readonly DashboardTrendsSnapshot $current,
        public readonly DashboardTrendsSnapshot $previous,
        private readonly array $metrics
    ) {
    }

    /**
     * Return all comparisons indexed by metric key.
     *
     * @return DashboardTrendComparison[]
     */
    public function metrics(): array {
        return $this->metrics;
    }

    /**
     * Return one trend comparison.
     */
    public function metric(
        string $key
    ): ?DashboardTrendComparison {
        return $this->metrics[$key] ?? null;
    }

    /**
     * Return the comparisons ordered for Dashboard presentation.
     *
     * Critical degradations come first, then warnings,
     * improvements and finally neutral items.
     *
     * @return DashboardTrendComparison[]
     */
    public function prioritized_metrics(): array {
        $metrics = array_values(
            $this->metrics
        );

        usort(
            $metrics,
            static function (
                DashboardTrendComparison $left,
                DashboardTrendComparison $right
            ): int {
                $leftpriority =
                    self::priority($left);

                $rightpriority =
                    self::priority($right);

                if ($leftpriority !== $rightpriority) {
                    return $rightpriority
                        <=> $leftpriority;
                }

                $leftvalue =
                    $left->current->value;

                $rightvalue =
                    $right->current->value;

                if ($leftvalue !== $rightvalue) {
                    return $rightvalue
                        <=> $leftvalue;
                }

                return strcmp(
                    $left->current->key,
                    $right->current->key
                );
            }
        );

        return $metrics;
    }

    /**
     * Internal presentation priority.
     */
    private static function priority(
        DashboardTrendComparison $comparison
    ): int {
        if (
            $comparison->business_is_degrading()
            && $comparison->current->severity ===
                DashboardTrendMetric::SEVERITY_CRITICAL
        ) {
            return 500;
        }

        if ($comparison->business_is_degrading()) {
            return 400;
        }

        if ($comparison->business_is_improving()) {
            return 300;
        }

        if ($comparison->current->has_value()) {
            return 200;
        }

        return 100;
    }
}