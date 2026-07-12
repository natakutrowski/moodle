<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardIssueService;

final class AlertsCard implements DashboardCard {

    public static function render(): string {
        $issues = (new DashboardIssueService())->load();

        $activeissues = array_filter(
            $issues,
            static fn($issue): bool => $issue->has_items()
        );

        $out = html_writer::start_div(
            'card card-body local-subscriptions-dashboard-card crm-dashboard-panel crm-issues-card'
        );

        $out .= html_writer::start_div('crm-dashboard-panel-header');
        $out .= html_writer::start_div();

        $out .= html_writer::tag(
            'h3',
            '⚠️ ' . get_string('dashboard_issues_title', 'local_subscriptions'),
            ['class' => 'h5 mb-1']
        );

        $out .= html_writer::div(
            get_string('dashboard_issues_subtitle', 'local_subscriptions'),
            'text-muted small'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        if (!$activeissues) {
            $out .= html_writer::div(
                html_writer::div(
                    '✓',
                    'crm-issues-empty-icon'
                ) .
                html_writer::div(
                    get_string(
                        'dashboard_issues_empty_title',
                        'local_subscriptions'
                    ),
                    'crm-issues-empty-title'
                ) .
                html_writer::div(
                    get_string(
                        'dashboard_issues_empty_description',
                        'local_subscriptions'
                    ),
                    'crm-issues-empty-description'
                ),
                'crm-issues-empty'
            );

            $out .= html_writer::end_div();

            return $out;
        }

        $out .= html_writer::start_div('crm-issues-list mt-3');

        foreach ($activeissues as $issue) {
            $classes = [
                'crm-issue-item',
                'crm-issue-' . $issue->severity,
            ];

            if ($issue->has_items()) {
                $classes[] = 'has-issue';
            }

            $out .= html_writer::start_div(implode(' ', $classes));

            $out .= html_writer::start_div('crm-issue-main');

            $out .= html_writer::div(
                (string)$issue->count,
                'crm-issue-count'
            );

            $out .= html_writer::start_div('crm-issue-body');

            $out .= html_writer::div(
                s($issue->title),
                'crm-issue-title'
            );

            $out .= html_writer::div(
                s($issue->description),
                'crm-issue-description'
            );

            $out .= html_writer::end_div();
            $out .= html_writer::end_div();

            if ($issue->has_primary_action()) {
                $out .= html_writer::link(
                    $issue->primaryactionurl,
                    s($issue->primaryactionlabel),
                    [
                        'class' => 'btn btn-sm btn-outline-primary crm-issue-action',
                    ]
                );
            }

            $out .= html_writer::end_div();
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }
}