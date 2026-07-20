<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
use local_subscriptions\crm\work\domain\WorkItemStatus;
use local_subscriptions\subscription_config;
use moodle_url;

final class WorkItemOverviewCard implements DashboardCard {

    public static function render(): string {
        global $DB, $USER;

        if (!AdminSecurity::can(Capabilities::VIEW_WORK_ITEMS)) {
            return '';
        }

        $mine = (int)$DB->count_records_select(
            'local_subscriptions_work_item',
            'assigneduserid = :userid AND status IN (:open, :progress, :blocked, :waiting)',
            [
                'userid' => (int)$USER->id,
                'open' => WorkItemStatus::OPEN,
                'progress' => WorkItemStatus::IN_PROGRESS,
                'blocked' => WorkItemStatus::BLOCKED,
                'waiting' => WorkItemStatus::WAITING,
            ]
        );

        $unassigned = (int)$DB->count_records_select(
            'local_subscriptions_work_item',
            'assigneduserid IS NULL AND assignedteamid IS NULL
             AND status IN (:open, :progress, :blocked, :waiting)',
            [
                'open' => WorkItemStatus::OPEN,
                'progress' => WorkItemStatus::IN_PROGRESS,
                'blocked' => WorkItemStatus::BLOCKED,
                'waiting' => WorkItemStatus::WAITING,
            ]
        );

        $urgent = (int)$DB->count_records_select(
            'local_subscriptions_work_item',
            'priority IN (:urgent, :critical)
             AND status IN (:open, :progress, :blocked, :waiting)',
            [
                'urgent' => 'urgent', 'critical' => 'critical',
                'open' => WorkItemStatus::OPEN,
                'progress' => WorkItemStatus::IN_PROGRESS,
                'blocked' => WorkItemStatus::BLOCKED,
                'waiting' => WorkItemStatus::WAITING,
            ]
        );

        $overdue = (int)$DB->count_records_select(
            'local_subscriptions_work_item',
            'dueat > 0 AND dueat < :now
             AND status IN (:open, :progress, :blocked, :waiting)',
            [
                'now' => time(),
                'open' => WorkItemStatus::OPEN,
                'progress' => WorkItemStatus::IN_PROGRESS,
                'blocked' => WorkItemStatus::BLOCKED,
                'waiting' => WorkItemStatus::WAITING,
            ]
        );

        $items = [
            [get_string('crm_work_my_items', 'local_subscriptions'), $mine, ['mineonly' => 1]],
            [get_string('crm_work_unassigned', 'local_subscriptions'), $unassigned, ['unassignedonly' => 1]],
            [get_string('crm_work_urgent', 'local_subscriptions'), $urgent, ['priority' => 'urgent']],
            [get_string('crm_work_overdue', 'local_subscriptions'), $overdue, ['overdueonly' => 1]],
        ];

        $content = DashboardCardUi::header(
            title: get_string(
                'crm_work_dashboard_title',
                'local_subscriptions'
            ),
            icon: '✅',
            actions: DashboardCardUi::action(
                new moodle_url(
                    subscription_config::
                        admin_work_items_page()
                ),
                get_string(
                    'dashboard_open_all',
                    'local_subscriptions'
                )
            ),
            titleid: 'crm-dashboard-work-title'
        );
        
        $content .= html_writer::start_div(
            'row g-2'
        );

        foreach (
            $items
            as [
                $label,
                $value,
                $params,
            ]
        ) {
            $metriccontent =
                html_writer::div(
                    (string)$value,
                    'h3 mb-1'
                ) .
                html_writer::div(
                    s($label),
                    'small'
                );

            $content .= html_writer::div(
                html_writer::link(
                    new moodle_url(
                        subscription_config::
                            admin_work_items_page(),
                        $params
                    ),
                    $metriccontent,
                    [
                        'class' =>
                            'crm-dashboard-work-metric ' .
                            'text-decoration-none h-100',
                    ]
                ),
                'col-6'
            );
        }

        $content .= html_writer::end_div();

        return DashboardCardUi::shell(
            content: $content,
            extraclasses: 'crm-dashboard-work-card',
            labelledby: 'crm-dashboard-work-title'
        );
    }
}