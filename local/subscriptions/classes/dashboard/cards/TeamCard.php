<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
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

        $content = DashboardCardUi::header(
            title: get_string(
                'dashboard_team_card_title',
                'local_subscriptions'
            ),
            icon: '👤',
            titleid: 'crm-dashboard-team-title'
        );

        $content .= html_writer::div(
            fullname($USER),
            'fw-bold'
        );

        $content .= html_writer::div(
            s($USER->email),
            'text-muted small mb-3'
        );

        $content .= html_writer::div(
            get_string(
                'dashboard_team_permissions',
                'local_subscriptions'
            ),
            'fw-semibold mb-2'
        );

        if ($items !== '') {
            $content .= $items;
        } else {
            $content .= DashboardCardUi::empty_state(
                title: get_string(
                    'dashboard_team_no_permissions',
                    'local_subscriptions'
                ),
                icon: 'ℹ',
                tone: DashboardCardUi::TONE_INFO
            );
        }

        $content .= DashboardCardUi::footer(
            get_string('lastaccess') .
            ': ' .
            AdminFormatter::datetime(
                (int)$USER->lastaccess
            )
        );

        return DashboardCardUi::shell(
            content: $content,
            extraclasses: 'crm-dashboard-team-card',
            labelledby: 'crm-dashboard-team-title'
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