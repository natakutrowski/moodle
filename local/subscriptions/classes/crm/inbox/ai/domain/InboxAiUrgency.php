<?php

namespace local_subscriptions\crm\inbox\ai\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAiUrgency {

    public const LOW = 'low';
    public const NORMAL = 'normal';
    public const HIGH = 'high';
    public const CRITICAL = 'critical';

    public static function values(): array {
        return [
            self::LOW,
            self::NORMAL,
            self::HIGH,
            self::CRITICAL,
        ];
    }

    public static function is_valid(
        string $urgency
    ): bool {
        return in_array(
            $urgency,
            self::values(),
            true
        );
    }

    public static function weight(
        string $urgency
    ): int {
        return match ($urgency) {
            self::CRITICAL => 100,
            self::HIGH => 75,
            self::NORMAL => 50,
            self::LOW => 25,
            default => 0,
        };
    }
}