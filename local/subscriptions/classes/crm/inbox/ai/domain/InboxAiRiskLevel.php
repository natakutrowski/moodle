<?php

namespace local_subscriptions\crm\inbox\ai\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAiRiskLevel {

    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const CRITICAL = 'critical';

    public static function values(): array {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::CRITICAL,
        ];
    }

    public static function is_valid(
        string $level
    ): bool {
        return in_array(
            $level,
            self::values(),
            true
        );
    }
}