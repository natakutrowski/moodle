<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable identifiers used by the CRM application navigation.
 */
final class CrmNavigationKeys {

    public const DASHBOARD = 'dashboard';

    public const USERS = 'users';

    public const INBOX = 'inbox';

    public const WORK = 'work';

    public const ASSISTANT = 'assistant';

    public const COMMERCE = 'commerce';

    public const SHOWROOMS = 'showrooms';

    public const HELP = 'help';

    public const TOOLS = 'tools';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::DASHBOARD,
            self::USERS,
            self::INBOX,
            self::WORK,
            self::ASSISTANT,
            self::COMMERCE,
            self::SHOWROOMS,
            self::HELP,
            self::TOOLS,
        ];
    }

    public static function is_valid(string $key): bool {
        return in_array(
            $key,
            self::all(),
            true
        );
    }
}