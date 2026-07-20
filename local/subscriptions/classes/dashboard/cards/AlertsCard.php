<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
use local_subscriptions\dashboard\services\DashboardIssueService;

final class AlertsCard implements DashboardCard {

    public static function render(): string {
        $issues = (new DashboardIssueService())->load();

        $activeissues = array_filter(
            $issues,
            static fn($issue): bool => $issue->has_items()
        );

        $content = DashboardCardUi::header(
            title: get_string(
                'dashboard_issues_title',
                'local_subscriptions'
            ),
            icon: '⚠️',
            subtitle: get_string(
                'dashboard_issues_subtitle',
                'local_subscriptions'
            ),
            titleid: 'crm-dashboard-issues-title'
        );

        if (!$activeissues) {
            $content .= DashboardCardUi::empty_state(
                title: get_string(
                    'dashboard_issues_empty_title',
                    'local_subscriptions'
                ),
                description: get_string(
                    'dashboard_issues_empty_description',
                    'local_subscriptions'
                ),
                icon: '✓',
                tone: DashboardCardUi::TONE_SUCCESS
            );

            return DashboardCardUi::shell(
                content: $content,
                extraclasses: 'crm-issues-card',
                labelledby: 'crm-dashboard-issues-title'
            );
        }

        $content .= html_writer::start_div('crm-issues-list mt-3');

        foreach ($activeissues as $issue) {
            $classes = [
                'crm-issue-item',
                'crm-issue-' . $issue->severity,
            ];

            if ($issue->has_items()) {
                $classes[] = 'has-issue';
            }

            $content .= html_writer::start_div(implode(' ', $classes));

            $content .= html_writer::start_div('crm-issue-main');

            $content .= html_writer::div(
                (string)$issue->count,
                'crm-issue-count'
            );

            $content .= html_writer::start_div('crm-issue-body');

            $content .= html_writer::div(
                s($issue->title),
                'crm-issue-title'
            );

            $content .= html_writer::div(
                s($issue->description),
                'crm-issue-description'
            );

            $content .= html_writer::end_div();
            $content .= html_writer::end_div();

            if ($issue->has_primary_action()) {
                $content .= html_writer::link(
                    $issue->primaryactionurl,
                    s($issue->primaryactionlabel),
                    [
                        'class' => 'btn btn-sm btn-outline-primary crm-issue-action',
                    ]
                );
            }

            $content .= html_writer::end_div();
        }

        $content .= html_writer::end_div();

        return DashboardCardUi::shell(
            content: $content,
            extraclasses: 'crm-issues-card',
            labelledby: 'crm-dashboard-issues-title'
        );
    }
}