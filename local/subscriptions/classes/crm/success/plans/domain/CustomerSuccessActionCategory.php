<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalized action categories used by the Customer Success planner.
 */
final class CustomerSuccessActionCategory {

    public const PAYMENT = 'payment';
    public const ACCESS = 'access';
    public const SUPPORT = 'support';
    public const COMMUNICATION = 'communication';
    public const LEARNING = 'learning';
    public const RETENTION = 'retention';
    public const COMMERCIAL = 'commercial';
    public const ADMINISTRATIVE = 'administrative';
    public const OTHER = 'other';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::PAYMENT,
            self::ACCESS,
            self::SUPPORT,
            self::COMMUNICATION,
            self::LEARNING,
            self::RETENTION,
            self::COMMERCIAL,
            self::ADMINISTRATIVE,
            self::OTHER,
        ];
    }

    public static function is_valid(
        string $category
    ): bool {
        return in_array(
            $category,
            self::all(),
            true
        );
    }
}