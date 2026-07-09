<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

final class LeadScoreLevel {

    public const VERY_LOW = 'very_low';
    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const EXCELLENT = 'excellent';

    public static function from_score(int $score): string {
        if ($score >= 81) {
            return self::EXCELLENT;
        }

        if ($score >= 61) {
            return self::HIGH;
        }

        if ($score >= 41) {
            return self::MEDIUM;
        }

        if ($score >= 21) {
            return self::LOW;
        }

        return self::VERY_LOW;
    }
}