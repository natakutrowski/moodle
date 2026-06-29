<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\subscription_config;

final class AdminSecurity {

    public static function require(string $capability): context_system {
        require_login();

        $context = context_system::instance();

        require_capability($capability, $context);
        subscription_config::guard_public_access();

        return $context;
    }

    public static function can(string $capability): bool {
        return has_capability($capability, context_system::instance());
    }
}