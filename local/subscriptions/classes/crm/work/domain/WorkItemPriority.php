<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemPriority {

    public const LOW = 'low';
    public const NORMAL = 'normal';
    public const HIGH = 'high';
    public const URGENT = 'urgent';
    public const CRITICAL = 'critical';

    public static function all(): array {
        return [
            self::LOW,
            self::NORMAL,
            self::HIGH,
            self::URGENT,
            self::CRITICAL,
        ];
    }

    public static function is_valid(string $priority): bool {
        return in_array($priority, self::all(), true);
    }
}