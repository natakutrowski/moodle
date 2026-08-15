<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

final class CommerceMailLibrary {
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_TRANSACTIONAL = 'transactional';
    public const CATEGORY_PERSONAL_OFFER = 'personal_offer';
    public const CATEGORY_SALES_FOLLOWUP = 'sales_followup';
    public const CATEGORY_SYSTEM = 'system';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const SOURCE_NATIVE = 'native';
    public const SOURCE_TRANSACTIONAL = 'transactional';

    public const SCHEMA = 'campusfr-mail-template';
    public const SCHEMA_VERSION = 1;
    public const BUILDER_VERSION = 2;
    public const LANGUAGES = ['fr', 'en', 'ru'];

    public static function categories(): array {
        return [
            self::CATEGORY_MARKETING,
            self::CATEGORY_TRANSACTIONAL,
            self::CATEGORY_PERSONAL_OFFER,
            self::CATEGORY_SALES_FOLLOWUP,
            self::CATEGORY_SYSTEM,
        ];
    }

    public static function statuses(): array {
        return [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_ARCHIVED];
    }

    public static function uuid(): string {
        return bin2hex(random_bytes(16));
    }

    private function __construct() {}
}
