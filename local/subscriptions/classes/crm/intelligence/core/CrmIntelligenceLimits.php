<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceLimits {

    public const DASHBOARD_USERS = 50;
    public const DASHBOARD_PRIORITY_USERS = 50;
    public const DASHBOARD_ALERT_USERS = 50;
    public const DASHBOARD_PROFILES = 5;
    public const DASHBOARD_ALERTS = 10;
    public const DASHBOARD_PRIORITIES = 10;

    public const SNAPSHOT_USERS = 200;
    public const SNAPSHOT_BATCH_SIZE = 200;
    public const HISTORY_RETENTION_DAYS = 180;
}