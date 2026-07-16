<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemType {

    public const TASK = 'task';
    public const SUPPORT = 'support';
    public const BUG = 'bug';
    public const INCIDENT = 'incident';
    public const FEATURE = 'feature';
    public const CONTENT = 'content';
    public const MARKETING = 'marketing';
    public const FINANCE = 'finance';
    public const ADMINISTRATION = 'administration';
    public const FOLLOW_UP = 'follow_up';

    public static function all(): array {
        return [
            self::TASK,
            self::SUPPORT,
            self::BUG,
            self::INCIDENT,
            self::FEATURE,
            self::CONTENT,
            self::MARKETING,
            self::FINANCE,
            self::ADMINISTRATION,
            self::FOLLOW_UP,
        ];
    }

    public static function is_valid(string $type): bool {
        return in_array($type, self::all(), true);
    }
}