<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardStatsService;

final class StatsCard implements DashboardCard {

    public static function render(): string {
        $stats = DashboardStatsService::load_today();

        $cards = [
            ['👤', get_string('dashboard_stats_new_users', 'local_subscriptions'), $stats->newusers],
            ['📚', get_string('dashboard_stats_new_subscriptions', 'local_subscriptions'), $stats->newsubscriptions],
            ['📦', get_string('dashboard_stats_digital_purchases', 'local_subscriptions'), $stats->digitalpurchases],
            ['💶', get_string('dashboard_stats_revenue', 'local_subscriptions'), $stats->revenue],
        ];

        $out = html_writer::tag('h3', get_string('dashboard_today', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        $out .= html_writer::start_div('row mb-4');

        foreach ($cards as [$icon, $label, $value]) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div($icon, 'dashboard-stat-icon') .
                    html_writer::div($value, 'crm-stat-number') .
                    html_writer::div($label, 'text-muted'),
                    'card card-body local-subscriptions-dashboard-card'
                ),
                'col-md-6 col-xl-3 mb-3'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }
}