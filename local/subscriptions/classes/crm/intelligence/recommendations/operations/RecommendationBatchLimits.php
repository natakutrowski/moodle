<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations;

defined('MOODLE_INTERNAL') || die();

/**
 * Operational limits for scheduled recommendation generation.
 */
final class RecommendationBatchLimits {

    /**
     * Maximum users evaluated by one normal scheduled run.
     */
    public const DEFAULT_USER_LIMIT = 250;

    /**
     * Number of candidate users loaded per database page.
     */
    public const DATABASE_PAGE_SIZE = 100;

    /**
     * Hard protection for manual executions.
     */
    public const MAX_USER_LIMIT = 2000;

    /**
     * Users active within this number of days remain candidates.
     */
    public const RECENT_ACTIVITY_DAYS = 90;

    /**
     * Recommendation run reports are kept for this number of days.
     */
    public const RUN_RETENTION_DAYS = 90;

    /**
     * A running report older than this delay is considered abandoned.
     */
    public const ABANDONED_RUN_MINUTES = 120;

    /**
     * Lock acquisition delay.
     *
     * Scheduled tasks must skip rather than wait for another long run.
     */
    public const LOCK_TIMEOUT_SECONDS = 0;

    public static function normalize_limit(
        int $limit
    ): int {
        return max(
            1,
            min(
                self::MAX_USER_LIMIT,
                $limit
            )
        );
    }
}