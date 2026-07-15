<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadStatus {

    public const OPEN = 'open';
    public const PENDING = 'pending';
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';
    public const SPAM = 'spam';

    public static function values(): array {
        return [
            self::OPEN,
            self::PENDING,
            self::RESOLVED,
            self::CLOSED,
            self::SPAM,
        ];
    }

    public static function is_valid(string $status): bool {
        return in_array(
            $status,
            self::values(),
            true
        );
    }
}