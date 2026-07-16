<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemStatus {

    public const OPEN = 'open';
    public const IN_PROGRESS = 'in_progress';
    public const BLOCKED = 'blocked';
    public const WAITING = 'waiting';
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';

    public static function all(): array {
        return [
            self::OPEN,
            self::IN_PROGRESS,
            self::BLOCKED,
            self::WAITING,
            self::RESOLVED,
            self::CLOSED,
            self::CANCELLED,
        ];
    }

    public static function active(): array {
        return [
            self::OPEN,
            self::IN_PROGRESS,
            self::BLOCKED,
            self::WAITING,
        ];
    }

    public static function terminal(): array {
        return [
            self::RESOLVED,
            self::CLOSED,
            self::CANCELLED,
        ];
    }

    public static function is_valid(string $status): bool {
        return in_array($status, self::all(), true);
    }
}