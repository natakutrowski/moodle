<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxPriority {

    public const LOW = 'low';
    public const NORMAL = 'normal';
    public const HIGH = 'high';
    public const URGENT = 'urgent';

    public static function values(): array {
        return [
            self::LOW,
            self::NORMAL,
            self::HIGH,
            self::URGENT,
        ];
    }

    public static function is_valid(string $priority): bool {
        return in_array(
            $priority,
            self::values(),
            true
        );
    }
}