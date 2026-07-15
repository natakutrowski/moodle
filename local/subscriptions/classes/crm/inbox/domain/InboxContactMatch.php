<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxContactMatch {

    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_AUTOMATIC = 'matched_automatic';
    public const STATUS_MANUAL = 'matched_manual';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_DETACHED = 'detached';

    public const SOURCE_NONE = 'none';
    public const SOURCE_MOODLE_EMAIL = 'moodle_email';
    public const SOURCE_PURCHASE_EMAIL = 'purchase_email';
    public const SOURCE_SUBSCRIPTION_EMAIL =
        'subscription_email';
    public const SOURCE_MANUAL = 'manual';

    public static function valid_statuses(): array {
        return [
            self::STATUS_UNMATCHED,
            self::STATUS_AUTOMATIC,
            self::STATUS_MANUAL,
            self::STATUS_AMBIGUOUS,
            self::STATUS_DETACHED,
        ];
    }

    public static function valid_sources(): array {
        return [
            self::SOURCE_NONE,
            self::SOURCE_MOODLE_EMAIL,
            self::SOURCE_PURCHASE_EMAIL,
            self::SOURCE_SUBSCRIPTION_EMAIL,
            self::SOURCE_MANUAL,
        ];
    }
}