<?php

namespace local_subscriptions\crm\inbox\ai\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAiStatus {

    public const SUCCESS = 'success';
    public const PARTIAL = 'partial';
    public const UNAVAILABLE = 'unavailable';
    public const BLOCKED = 'blocked';
    public const FAILED = 'failed';

    public static function values(): array {
        return [
            self::SUCCESS,
            self::PARTIAL,
            self::UNAVAILABLE,
            self::BLOCKED,
            self::FAILED,
        ];
    }

    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::values(),
            true
        );
    }
}