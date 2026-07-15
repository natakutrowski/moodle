<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxParticipantType {

    public const FROM = 'from';
    public const TO = 'to';
    public const CC = 'cc';
    public const BCC = 'bcc';
    public const REPLY_TO = 'replyto';

    public static function values(): array {
        return [
            self::FROM,
            self::TO,
            self::CC,
            self::BCC,
            self::REPLY_TO,
        ];
    }

    public static function is_valid(string $type): bool {
        return in_array(
            $type,
            self::values(),
            true
        );
    }
}