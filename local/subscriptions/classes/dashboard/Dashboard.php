<?php

namespace local_subscriptions\dashboard;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\cards\TeamCard;
use local_subscriptions\dashboard\cards\NavigationCard;
use local_subscriptions\dashboard\cards\StatsCard;
use local_subscriptions\dashboard\cards\AlertsCard;
use local_subscriptions\dashboard\cards\ActivityCard;
use local_subscriptions\dashboard\cards\CrmIntelligenceCard;
use local_subscriptions\dashboard\cards\CrmIntelligenceAlertsCard;
use local_subscriptions\dashboard\cards\CrmFunnelCard;
use local_subscriptions\dashboard\cards\CrmTrendsCard;
use local_subscriptions\dashboard\cards\CrmDailyPrioritiesCard;
use local_subscriptions\dashboard\cards\InboxOverviewCard;

final class Dashboard {

    public static function render(string $period = \local_subscriptions\dashboard\services\DashboardPeriod::TODAY): string {
        $out = html_writer::start_div('local-subscriptions-dashboard-workspace');

        $out .= html_writer::tag(
            'p',
            get_string('admin_dashboard_intro', 'local_subscriptions'),
            ['class' => 'lead text-muted mb-4']
        );

        $out .= html_writer::start_div('local-subscriptions-dashboard-grid');

        $out .= html_writer::start_div('local-subscriptions-dashboard-main');
        $out .= StatsCard::render($period);

        $out .= html_writer::start_div('crm-dashboard-panels-grid');

        $out .= html_writer::div(
            CrmIntelligenceCard::render(),
            'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
        );

        $inboxcard = InboxOverviewCard::render();

        if ($inboxcard !== '') {
            $out .= html_writer::div(
                $inboxcard,
                'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
            );
        }

        $out .= html_writer::div(
            AlertsCard::render(),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            CrmDailyPrioritiesCard::render(),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            CrmFunnelCard::render(),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            CrmTrendsCard::render(),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            CrmIntelligenceAlertsCard::render(),
            'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
        );

        $out .= html_writer::end_div();

        $out .= DashboardSection::render([
            NavigationCard::class,
        ]);

        $out .= DashboardSection::render([
            ActivityCard::class,
        ], 'd-block');
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('local-subscriptions-dashboard-side');
        $out .= DashboardSection::render([
            TeamCard::class,
        ], 'd-block');
        $out .= html_writer::end_div();

        $out .= html_writer::end_div();

        $out .= html_writer::end_div();
        return $out;
    }
}