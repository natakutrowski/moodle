<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable history actions for persistent recommendations.
 */
final class RecommendationHistoryAction {

    public const CREATED = 'created';
    public const REFRESHED = 'refreshed';
    public const REOPENED = 'reopened';
    public const ACCEPTED = 'accepted';
    public const DISMISSED = 'dismissed';
    public const COMPLETED = 'completed';
    public const EXPIRED = 'expired';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::CREATED,
            self::REFRESHED,
            self::REOPENED,
            self::ACCEPTED,
            self::DISMISSED,
            self::COMPLETED,
            self::EXPIRED,
        ];
    }

    public static function is_valid(string $action): bool {
        return in_array(
            $action,
            self::all(),
            true
        );
    }
}