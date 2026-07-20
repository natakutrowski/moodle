<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\dashboard\trends\DashboardTrendsRepository;

/**
 * Normalizes Dashboard trend drill-down parameters.
 */
final class UserExplorerTrendFilter {

    /**
     * Maximum allowed observation interval.
     *
     * This protects the Explorer from accidental excessively large ranges.
     */
    private const MAX_RANGE_SECONDS =
        370 * DAYSECS;

    /**
     * Minimum significant score variation.
     */
    private const MIN_DELTA = 1;

    /**
     * Maximum accepted score variation threshold.
     */
    private const MAX_DELTA = 100;

    public function __construct(
        public readonly string $trend,
        public readonly int $start,
        public readonly int $end,
        public readonly int $delta
    ) {
    }

    /**
     * Build a normalized filter.
     */
    public static function create(
        string $trend,
        int $start,
        int $end,
        int $delta =
            DashboardTrendsRepository::
                DEFAULT_SIGNIFICANT_DELTA
    ): self {
        $trend = self::normalize_trend($trend);
        $start = max(0, $start);
        $end = max(0, $end);

        $delta = max(
            self::MIN_DELTA,
            min(
                self::MAX_DELTA,
                $delta
            )
        );

        if (
            $trend === ''
            || $start <= 0
            || $end <= $start
            || ($end - $start) >
                self::MAX_RANGE_SECONDS
        ) {
            return new self(
                '',
                0,
                0,
                $delta
            );
        }

        return new self(
            $trend,
            $start,
            $end,
            $delta
        );
    }

    /**
     * Empty filter.
     */
    public static function none(): self {
        return new self(
            '',
            0,
            0,
            DashboardTrendsRepository::
                DEFAULT_SIGNIFICANT_DELTA
        );
    }

    /**
     * Whether the drill-down filter is active.
     */
    public function is_active(): bool {
        return
            $this->trend !== ''
            && $this->start > 0
            && $this->end > $this->start;
    }

    /**
     * Return URL/saved-view parameters.
     *
     * @return array<string, int|string>
     */
    public function params(): array {
        if (!$this->is_active()) {
            return [];
        }

        return [
            'trend' => $this->trend,
            'trendstart' => $this->start,
            'trendend' => $this->end,
            'trenddelta' => $this->delta,
        ];
    }

    /**
     * Return the score-column delta represented by this trend.
     */
    public function score_field(): string {
        return match ($this->trend) {
            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_UP,
            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_DOWN =>
                    'engagementscore',

            DashboardTrendsRepository::
                METRIC_RISK_UP,
            DashboardTrendsRepository::
                METRIC_RISK_DOWN =>
                    'riskscore',

            DashboardTrendsRepository::
                METRIC_GLOBAL_UP,
            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN =>
                    'globalscore',

            default => '',
        };
    }

    /**
     * Whether the current score must be higher than the baseline.
     */
    public function expects_increase(): bool {
        return in_array(
            $this->trend,
            [
                DashboardTrendsRepository::
                    METRIC_ENGAGEMENT_UP,

                DashboardTrendsRepository::
                    METRIC_RISK_UP,

                DashboardTrendsRepository::
                    METRIC_GLOBAL_UP,
            ],
            true
        );
    }

    /**
     * Normalize a supported trend key.
     */
    public static function normalize_trend(
        string $trend
    ): string {
        $trend = clean_param(
            $trend,
            PARAM_ALPHANUMEXT
        );

        $allowed = [
            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_UP,

            DashboardTrendsRepository::
                METRIC_ENGAGEMENT_DOWN,

            DashboardTrendsRepository::
                METRIC_RISK_UP,

            DashboardTrendsRepository::
                METRIC_RISK_DOWN,

            DashboardTrendsRepository::
                METRIC_GLOBAL_UP,

            DashboardTrendsRepository::
                METRIC_GLOBAL_DOWN,
        ];

        return in_array(
            $trend,
            $allowed,
            true
        ) ? $trend : '';
    }
}