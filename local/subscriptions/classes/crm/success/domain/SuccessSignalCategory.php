<?php

namespace local_subscriptions\crm\success\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Customer Success dimensions affected by normalized signals.
 */
final class SuccessSignalCategory {

    public const COMMERCIAL = 'commercial';
    public const ENGAGEMENT = 'engagement';
    public const LEARNING = 'learning';
    public const SUPPORT = 'support';
    public const LOYALTY = 'loyalty';
    public const RISK = 'risk';
    public const GAMIFICATION = 'gamification';

    /**
     * Returns all supported signal categories.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::COMMERCIAL,
            self::ENGAGEMENT,
            self::LEARNING,
            self::SUPPORT,
            self::LOYALTY,
            self::RISK,
            self::GAMIFICATION,
        ];
    }

    public static function is_valid(string $category): bool {
        return in_array($category, self::all(), true);
    }
}