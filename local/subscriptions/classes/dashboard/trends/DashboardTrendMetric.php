<?php

namespace local_subscriptions\dashboard\trends;

defined('MOODLE_INTERNAL') || die();

/**
 * One aggregated CRM trend metric for a period.
 */
final class DashboardTrendMetric {

    public const DIRECTION_IMPROVING = 'improving';
    public const DIRECTION_DEGRADING = 'degrading';
    public const DIRECTION_STABLE = 'stable';
    public const DIRECTION_UNAVAILABLE = 'unavailable';

    public const SEVERITY_POSITIVE = 'positive';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_NEUTRAL = 'neutral';

    /**
     * @param string $key Stable technical metric key.
     * @param int $value Number of distinct affected users.
     * @param string $direction Business direction.
     * @param string $severity Display severity.
     * @param int[] $userids Distinct affected user IDs.
     */
    public function __construct(
        public readonly string $key,
        public readonly int $value,
        public readonly string $direction,
        public readonly string $severity,
        public readonly array $userids = []
    ) {
    }

    /**
     * Whether the metric has affected users.
     */
    public function has_value(): bool {
        return $this->value > 0;
    }

    /**
     * Whether this metric represents a business improvement.
     */
    public function is_improving(): bool {
        return $this->direction ===
            self::DIRECTION_IMPROVING;
    }

    /**
     * Whether this metric represents a business degradation.
     */
    public function is_degrading(): bool {
        return $this->direction ===
            self::DIRECTION_DEGRADING;
    }

    /**
     * Return unique positive user IDs.
     *
     * @return int[]
     */
    public function normalized_userids(): array {
        $userids = array_map(
            'intval',
            $this->userids
        );

        $userids = array_filter(
            $userids,
            static fn(int $userid): bool =>
                $userid > 0
        );

        return array_values(
            array_unique($userids)
        );
    }
}