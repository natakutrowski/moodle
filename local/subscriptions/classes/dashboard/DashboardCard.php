<?php

namespace local_subscriptions\dashboard;

defined('MOODLE_INTERNAL') || die();

interface DashboardCard {
    public static function render(): string;
}