<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Risk levels displayed for CRM administrative tools.
 */
final class AdminToolRiskLevels {

    public const LOW = 'low';

    public const NORMAL = 'normal';

    public const HIGH = 'high';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::LOW,
            self::NORMAL,
            self::HIGH,
        ];
    }

    public static function is_valid(
        string $risklevel
    ): bool {
        return in_array(
            $risklevel,
            self::all(),
            true
        );
    }
}