<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle states of a recommendation.
 *
 * A recommendation is always advisory. Accepting a recommendation records
 * the administrator's decision but does not imply that every proposed action
 * has already been completed.
 */
final class RecommendationStatus {

    /**
     * Recommendation is currently proposed to administrators.
     */
    public const PROPOSED = 'proposed';

    /**
     * Recommendation has been acknowledged and accepted by an administrator.
     */
    public const ACCEPTED = 'accepted';

    /**
     * Recommendation has deliberately been dismissed.
     */
    public const DISMISSED = 'dismissed';

    /**
     * Recommended operational action has been completed.
     */
    public const COMPLETED = 'completed';

    /**
     * Recommendation is no longer relevant because its source condition
     * disappeared or its validity period ended.
     */
    public const EXPIRED = 'expired';

    /**
     * Return all supported recommendation statuses.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::PROPOSED,
            self::ACCEPTED,
            self::DISMISSED,
            self::COMPLETED,
            self::EXPIRED,
        ];
    }

    /**
     * Check whether a recommendation status is supported.
     */
    public static function is_valid(string $status): bool {
        return in_array($status, self::all(), true);
    }

    /**
     * Return statuses that represent an active recommendation.
     *
     * @return string[]
     */
    public static function active(): array {
        return [
            self::PROPOSED,
            self::ACCEPTED,
        ];
    }

    /**
     * Check whether a recommendation is still active.
     */
    public static function is_active(string $status): bool {
        return in_array($status, self::active(), true);
    }

    /**
     * Return statuses that close the recommendation lifecycle.
     *
     * @return string[]
     */
    public static function terminal(): array {
        return [
            self::DISMISSED,
            self::COMPLETED,
            self::EXPIRED,
        ];
    }

    /**
     * Check whether a recommendation is in a terminal state.
     */
    public static function is_terminal(string $status): bool {
        return in_array($status, self::terminal(), true);
    }
}