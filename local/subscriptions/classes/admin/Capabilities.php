<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class Capabilities {
    public const VIEW_DASHBOARD = 'local/subscriptions:view_dashboard';
    public const VIEW_USERS = 'local/subscriptions:view_users';
    public const MANAGE_USERS = 'local/subscriptions:manage_users';
    public const MANAGE_SUBSCRIPTIONS = 'local/subscriptions:manage_subscriptions';
    public const VIEW_DIGITAL = 'local/subscriptions:view_digital';
    public const MANAGE_DIGITAL = 'local/subscriptions:manage_digital';
    public const VIEW_PAYMENTS = 'local/subscriptions:view_payments';
    public const VIEW_STATISTICS = 'local/subscriptions:view_statistics';

    public const VIEW_INBOX = 'local/subscriptions:view_inbox';
    public const MANAGE_INBOX = 'local/subscriptions:manage_inbox';
    public const MANAGE_CONFIGURATION = 'local/subscriptions:manage_configuration';
    public const USE_INBOX_AI = 'local/subscriptions:use_inbox_ai';

    public static function can_view_users(?\context $context = null): bool {
        $context = $context ?? \context_system::instance();

        return has_capability(self::VIEW_USERS, $context);
    }

    public static function can_manage_users(?\context $context = null): bool {
        $context = $context ?? \context_system::instance();

        return has_capability(self::MANAGE_USERS, $context);
    }

    public static function can_view_inbox(
        ?\context $context = null
    ): bool {
        $context = $context ?? \context_system::instance();

        return has_capability(self::VIEW_INBOX, $context);
    }

    public static function can_manage_inbox(
        ?\context $context = null
    ): bool {
        $context = $context ?? \context_system::instance();

        return has_capability(self::MANAGE_INBOX, $context);
    }    
}