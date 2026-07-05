<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\Capabilities;

final class TeamCard implements DashboardCard {

    public static function render(): string {
        global $USER;

        $permissions = self::permissions();

        $items = '';

        foreach ($permissions as $permission) {
            $items .= html_writer::div('✓ ' . $permission, 'small mb-1');
        }

        return html_writer::div(
            html_writer::tag('h3', '👤 ' . get_string('dashboard_team_card_title', 'local_subscriptions'), [
                'class' => 'h5 mb-3',
            ]) .
            html_writer::tag('div', fullname($USER), ['class' => 'fw-bold']) .
            html_writer::tag('div', s($USER->email), ['class' => 'text-muted small mb-3']) .
            html_writer::tag('div', get_string('dashboard_team_permissions', 'local_subscriptions'), [
                'class' => 'fw-semibold mb-2',
            ]) .
            ($items ?: html_writer::div(get_string('dashboard_team_no_permissions', 'local_subscriptions'), 'text-muted small')) .
            html_writer::tag('div', get_string('lastaccess') . ': ' . AdminFormatter::datetime((int)$USER->lastaccess), [
                'class' => 'text-muted small mt-3',
            ]),
            'card card-body local-subscriptions-dashboard-card'
        );
    }

    private static function permissions(): array {
        $permissions = [];

        $map = [
            Capabilities::VIEW_USERS => get_string('dashboard_permission_users', 'local_subscriptions'),
            Capabilities::MANAGE_SUBSCRIPTIONS => get_string('dashboard_permission_subscriptions', 'local_subscriptions'),
            Capabilities::VIEW_DIGITAL => get_string('dashboard_permission_digital', 'local_subscriptions'),
            Capabilities::VIEW_PAYMENTS => get_string('dashboard_permission_payments', 'local_subscriptions'),
            Capabilities::MANAGE_CONFIGURATION => get_string('dashboard_permission_configuration', 'local_subscriptions'),
        ];

        foreach ($map as $capability => $label) {
            if (has_capability($capability, \context_system::instance())) {
                $permissions[] = $label;
            }
        }

        return $permissions;
    }
}