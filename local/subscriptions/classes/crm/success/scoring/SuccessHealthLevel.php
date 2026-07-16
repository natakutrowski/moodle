<?php

namespace local_subscriptions\crm\success\scoring;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalized Customer Success health levels.
 */
final class SuccessHealthLevel {

    public const CRITICAL = 'critical';
    public const AT_RISK = 'at_risk';
    public const WATCH = 'watch';
    public const STABLE = 'stable';
    public const STRONG = 'strong';
    public const EXCELLENT = 'excellent';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::CRITICAL,
            self::AT_RISK,
            self::WATCH,
            self::STABLE,
            self::STRONG,
            self::EXCELLENT,
        ];
    }

    public static function from_score(
        int $score
    ): string {
        $score = max(
            0,
            min(100, $score)
        );

        if ($score >= 90) {
            return self::EXCELLENT;
        }

        if ($score >= 75) {
            return self::STRONG;
        }

        if ($score >= 60) {
            return self::STABLE;
        }

        if ($score >= 40) {
            return self::WATCH;
        }

        if ($score >= 20) {
            return self::AT_RISK;
        }

        return self::CRITICAL;
    }

    public static function is_valid(
        string $level
    ): bool {
        return in_array(
            $level,
            self::all(),
            true
        );
    }
}