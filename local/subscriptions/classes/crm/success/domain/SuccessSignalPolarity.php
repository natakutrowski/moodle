<?php

namespace local_subscriptions\crm\success\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Describes whether a signal is favourable, neutral or unfavourable.
 */
final class SuccessSignalPolarity {

    public const POSITIVE = 'positive';
    public const NEUTRAL = 'neutral';
    public const NEGATIVE = 'negative';

    /**
     * Returns all supported polarities.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::POSITIVE,
            self::NEUTRAL,
            self::NEGATIVE,
        ];
    }

    public static function is_valid(string $polarity): bool {
        return in_array($polarity, self::all(), true);
    }
}