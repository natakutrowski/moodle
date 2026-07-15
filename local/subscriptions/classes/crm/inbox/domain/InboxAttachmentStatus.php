<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAttachmentStatus {

    public const PENDING = 'pending';
    public const DOWNLOADING = 'downloading';
    public const STORED = 'stored';
    public const FAILED = 'failed';

    public static function values(): array {
        return [
            self::PENDING,
            self::DOWNLOADING,
            self::STORED,
            self::FAILED,
        ];
    }
}