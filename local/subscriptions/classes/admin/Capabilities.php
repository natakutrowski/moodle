<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class Capabilities {
    public const VIEW_DASHBOARD = 'local/subscriptions:view_dashboard';
    public const VIEW_USERS = 'local/subscriptions:view_users';
    public const MANAGE_SUBSCRIPTIONS = 'local/subscriptions:manage_subscriptions';
    public const VIEW_DIGITAL = 'local/subscriptions:view_digital';
    public const MANAGE_DIGITAL = 'local/subscriptions:manage_digital';
    public const VIEW_PAYMENTS = 'local/subscriptions:view_payments';
    public const VIEW_STATISTICS = 'local/subscriptions:view_statistics';
    public const MANAGE_CONFIGURATION = 'local/subscriptions:manage_configuration';
}