<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\services\DashboardStatsService;

final class StatsCard implements DashboardCard {

    public static function render(string $period = DashboardPeriod::TODAY): string {
        $period = DashboardPeriod::normalize($period);
        $stats = (new DashboardStatsService())->load($period);

        $cards = [
            ['👤', get_string('dashboard_stats_new_users', 'local_subscriptions'), $stats->newusers],
            ['📚', get_string('dashboard_stats_new_subscriptions', 'local_subscriptions'), $stats->newsubscriptions],
            ['📦', get_string('dashboard_stats_digital_purchases', 'local_subscriptions'), $stats->digitalpurchases],
            ['💶', get_string('dashboard_stats_revenue', 'local_subscriptions'), $stats->revenue],
        ];

        $out = html_writer::start_div('crm-dashboard-hero mb-4');

        $out .= html_writer::start_div('crm-dashboard-hero-header');
        $out .= html_writer::tag('h3', get_string('dashboard_command_center_title', 'local_subscriptions'), [
            'class' => 'h4 mb-0',
        ]);
        $out .= self::period_control($period);
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('row mt-3');

        foreach ($cards as [$icon, $label, $value]) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div($icon, 'dashboard-stat-icon') .
                    html_writer::div($value, 'crm-stat-number') .
                    html_writer::div($label, 'text-muted'),
                    'card card-body local-subscriptions-dashboard-card crm-dashboard-stat-card'
                ),
                'col-md-6 col-xl-3 mb-3'
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function period_control(string $active): string {
        $items = '';

        foreach (DashboardPeriod::allowed() as $period) {
            $classes = 'crm-dashboard-period-pill';

            if ($period === $active) {
                $classes .= ' active';
            }

            $items .= html_writer::link(
                new moodle_url(subscription_config::admin_dashboard_page(), ['period' => $period]),
                DashboardPeriod::label($period),
                ['class' => $classes]
            );
        }

        return html_writer::div($items, 'crm-dashboard-period-control');
    }
}