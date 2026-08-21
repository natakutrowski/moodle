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

        $rights = '';

        foreach ($permissions as $permission) {
            $rights .= html_writer::div(
                html_writer::span(
                    '✓',
                    'crm-dashboard-team-right-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                ) .
                html_writer::span(
                    s($permission),
                    'crm-dashboard-team-right-label'
                ),
                'crm-dashboard-team-right'
            );
        }

        $summary = html_writer::span(
            '👤',
            'crm-dashboard-team-summary-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $summary .= html_writer::span(
            get_string(
                'dashboard_team_card_title',
                'local_subscriptions'
            ),
            'crm-dashboard-team-summary-title'
        );

        $summary .= html_writer::span(
            fullname($USER),
            'crm-dashboard-team-summary-user'
        );

        $content = html_writer::tag(
            'summary',
            $summary,
            [
                'class' =>
                    'crm-dashboard-team-summary',
            ]
        );

        $details = html_writer::div(
            fullname($USER),
            'crm-dashboard-team-name'
        );

        $details .= html_writer::div(
            s($USER->email),
            'crm-dashboard-team-email'
        );

        $details .= html_writer::div(
            get_string(
                'dashboard_team_permissions',
                'local_subscriptions'
            ),
            'crm-dashboard-team-rights-title'
        );

        if ($rights !== '') {
            $details .= html_writer::div(
                $rights,
                'crm-dashboard-team-rights'
            );
        } else {
            $details .= DashboardCardUi::empty_state(
                title: get_string(
                    'dashboard_team_no_permissions',
                    'local_subscriptions'
                ),
                icon: 'ℹ',
                tone: DashboardCardUi::TONE_INFO
            );
        }

        $details .= html_writer::div(
            get_string(
                'dashboard_team_last_access_n1211b',
                'local_subscriptions',
                AdminFormatter::datetime(
                    (int)$USER->lastaccess
                )
            ),
            'crm-dashboard-team-last-access'
        );

        $content .= html_writer::div(
            $details,
            'crm-dashboard-team-details'
        );

        return html_writer::tag(
            'details',
            $content,
            [
                'class' =>
                    'crm-dashboard-team-card ' .
                    'local-subscriptions-dashboard-card',
            ]
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