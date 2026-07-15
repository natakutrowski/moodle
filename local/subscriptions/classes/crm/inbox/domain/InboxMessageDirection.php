<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxMessageDirection {

    public const INBOUND = 'inbound';
    public const OUTBOUND = 'outbound';

    public static function values(): array {
        return [
            self::INBOUND,
            self::OUTBOUND,
        ];
    }

    public static function is_valid(string $direction): bool {
        return in_array(
            $direction,
            self::values(),
            true
        );
    }
}