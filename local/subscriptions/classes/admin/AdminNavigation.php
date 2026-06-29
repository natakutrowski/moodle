<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;

final class AdminNavigation {

    public static function back_button(): string {
        return html_writer::link(
            new moodle_url(subscription_config::admin_dashboard_page()),
            '← ' . get_string('back_to_admin_dashboard', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary mb-3']
        );
    }

    public static function quick_actions(): string {
        $items = [];

        if (AdminSecurity::can(Capabilities::MANAGE_SUBSCRIPTIONS)) {
            $items[] = html_writer::link(
                new moodle_url(subscription_config::user_subscriptions_page()),
                '📋 ' . get_string('manage_user_subscriptions', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary']
            );

            $items[] = html_writer::link(
                new moodle_url(subscription_config::add_manual_subscription_page()),
                '➕ ' . get_string('add_subscription', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary']
            );
        }

        if (AdminSecurity::can(Capabilities::MANAGE_CONFIGURATION)) {
            $items[] = html_writer::link(
                new moodle_url(subscription_config::manage_page()),
                '🧩 ' . get_string('admin_card_plans_title', 'local_subscriptions'),
                ['class' => 'btn btn-outline-secondary']
            );
        }

        if (empty($items)) {
            return '';
        }

        return html_writer::div(
            implode(' ', $items),
            'local-subscriptions-admin-quick-actions mb-4'
        );
    }
}