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

final class Dashboard {

    public static function render(): string {
        $out = '';

        $out .= html_writer::tag(
            'p',
            get_string('admin_dashboard_intro', 'local_subscriptions'),
            ['class' => 'lead text-muted mb-4']
        );

        $out .= html_writer::start_div('local-subscriptions-dashboard-grid');

        $out .= html_writer::start_div('local-subscriptions-dashboard-main');
        $out .= DashboardSection::render([
            StatsCard::class,
        ], 'd-block');

        $out .= DashboardSection::render([
            CrmIntelligenceCard::class,
        ], 'd-block');

        $out .= DashboardSection::render([
            CrmDailyPrioritiesCard::class,
        ], 'd-block');

        $out .= DashboardSection::render([
            CrmIntelligenceAlertsCard::class,
        ], 'd-block');

        $out .= DashboardSection::render([
            CrmFunnelCard::class,
        ], 'd-block');

        $out .= DashboardSection::render([
            CrmTrendsCard::class,
        ], 'd-block');

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
            AlertsCard::class,
        ], 'd-block');
        $out .= html_writer::end_div();

        $out .= html_writer::end_div();

        return $out;
    }
}