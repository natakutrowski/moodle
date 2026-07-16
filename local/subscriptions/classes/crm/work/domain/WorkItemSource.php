<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemSource {

    public const MANUAL = 'manual';
    public const INBOX = 'inbox';
    public const USER_360 = 'user_360';
    public const DASHBOARD = 'dashboard';
    public const AUTOMATION = 'automation';
    public const INTELLIGENCE = 'intelligence';
    public const ASSISTANT = 'assistant';
    public const COMMAND_CENTER = 'command_center';
    public const SYSTEM = 'system';

    public static function all(): array {
        return [
            self::MANUAL,
            self::INBOX,
            self::USER_360,
            self::DASHBOARD,
            self::AUTOMATION,
            self::INTELLIGENCE,
            self::ASSISTANT,
            self::COMMAND_CENTER,
            self::SYSTEM,
        ];
    }

    public static function is_valid(string $source): bool {
        return in_array($source, self::all(), true);
    }
}