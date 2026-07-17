<?php

namespace local_subscriptions\crm\intelligence\recommendations\correlation;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalized confidence levels for cross-domain correlations.
 */
final class CorrelationConfidence {

    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const VERY_HIGH = 'very_high';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::VERY_HIGH,
        ];
    }

    public static function is_valid(string $level): bool {
        return in_array(
            $level,
            self::all(),
            true
        );
    }

    public static function is_valid_score(int $score): bool {
        return $score >= 0 && $score <= 100;
    }

    public static function from_score(int $score): string {
        if (!self::is_valid_score($score)) {
            throw new \InvalidArgumentException(
                'Correlation confidence score must be between 0 and 100.'
            );
        }

        if ($score >= 90) {
            return self::VERY_HIGH;
        }

        if ($score >= 75) {
            return self::HIGH;
        }

        if ($score >= 55) {
            return self::MEDIUM;
        }

        return self::LOW;
    }
}