<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxMessageStatus {

    public const RECEIVED = 'received';
    public const DRAFT = 'draft';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const FAILED = 'failed';

    public static function values(): array {
        return [
            self::RECEIVED,
            self::DRAFT,
            self::SENDING,
            self::SENT,
            self::FAILED,
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