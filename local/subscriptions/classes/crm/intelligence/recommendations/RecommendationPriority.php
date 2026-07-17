<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Recommendation priority levels and normalized numerical scores.
 *
 * Recommendation scores use a 0..100 range. The textual level makes the
 * recommendation easier to filter and display while preserving precise
 * ordering through the numerical score.
 */
final class RecommendationPriority {

    public const LOW = 'low';
    public const NORMAL = 'normal';
    public const HIGH = 'high';
    public const URGENT = 'urgent';
    public const CRITICAL = 'critical';

    /**
     * Return all supported priority levels.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::LOW,
            self::NORMAL,
            self::HIGH,
            self::URGENT,
            self::CRITICAL,
        ];
    }

    /**
     * Check whether a priority level is supported.
     */
    public static function is_valid(string $priority): bool {
        return in_array($priority, self::all(), true);
    }

    /**
     * Validate a numerical priority score.
     */
    public static function is_valid_score(int $score): bool {
        return $score >= 0 && $score <= 100;
    }

    /**
     * Resolve the textual priority level associated with a score.
     */
    public static function from_score(int $score): string {
        if (!self::is_valid_score($score)) {
            throw new \InvalidArgumentException(
                'Recommendation priority score must be between 0 and 100.'
            );
        }

        if ($score >= 95) {
            return self::CRITICAL;
        }

        if ($score >= 80) {
            return self::URGENT;
        }

        if ($score >= 60) {
            return self::HIGH;
        }

        if ($score >= 30) {
            return self::NORMAL;
        }

        return self::LOW;
    }

    /**
     * Return the default score associated with a textual priority.
     */
    public static function default_score(string $priority): int {
        return match ($priority) {
            self::LOW => 15,
            self::NORMAL => 45,
            self::HIGH => 70,
            self::URGENT => 87,
            self::CRITICAL => 97,
            default => throw new \InvalidArgumentException(
                'Invalid recommendation priority.'
            ),
        };
    }
}