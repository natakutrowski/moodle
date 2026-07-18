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
use local_subscriptions\dashboard\cards\WorkItemOverviewCard;
use local_subscriptions\dashboard\cards\CrmAssistantCard;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanDashboardCard;
use local_subscriptions\dashboard\runtime\DashboardCardProfiler;

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

        $out .= self::render_card(
            'StatsCard',
            static fn(): string => StatsCard::render($period)
        );        

        $out .= html_writer::start_div('crm-dashboard-panels-grid');

        $out .= html_writer::div(
            self::render_card(
                'CrmIntelligenceCard',
                static fn(): string =>
                    CrmIntelligenceCard::render()
            ),
            'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
        );

        $assistantcard = self::render_card(
            'CrmAssistantCard',
            static fn(): string => CrmAssistantCard::render()
        );

        if ($assistantcard !== '') {
            $out .= html_writer::div(
                $assistantcard,
                'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
            );
        }

        $inboxcard = self::render_card(
            'InboxOverviewCard',
            static fn(): string => InboxOverviewCard::render()
        );

        if ($inboxcard !== '') {
            $out .= html_writer::div(
                $inboxcard,
                'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
            );
        }

        $workcard = self::render_card(
            'WorkItemOverviewCard',
            static fn(): string => WorkItemOverviewCard::render()
        );

        if ($workcard !== '') {
            $out .= html_writer::div(
                $workcard,
                'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
            );
        }

        $successplancard = self::render_card(
            'CustomerSuccessPlanDashboardCard',
            static fn(): string =>
                (new CustomerSuccessPlanDashboardCard())
                    ->render()
        );

        if ($successplancard !== '') {
            $out .= html_writer::div(
                $successplancard,
                'crm-dashboard-panel-slot crm-dashboard-panel-slot-span-2'
            );
        }

        $out .= html_writer::div(
            self::render_card(
                'AlertsCard',
                static fn(): string => AlertsCard::render()
            ),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            self::render_card(
                'CrmDailyPrioritiesCard',
                static fn(): string =>
                    CrmDailyPrioritiesCard::render()
            ),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            self::render_card(
                'CrmFunnelCard',
                static fn(): string => CrmFunnelCard::render()
            ),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            self::render_card(
                'CrmTrendsCard',
                static fn(): string => CrmTrendsCard::render()
            ),
            'crm-dashboard-panel-slot'
        );

        $out .= html_writer::div(
            self::render_card(
                'CrmIntelligenceAlertsCard',
                static fn(): string =>
                    CrmIntelligenceAlertsCard::render()
            ),
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

    /**
     * Renders a Card through the Dashboard runtime profiler.
     *
     * @param string $cardname Stable technical Card name.
     * @param callable $renderer Rendering callback.
     * @return string
     */
    private static function render_card(
        string $cardname,
        callable $renderer
    ): string {
        return DashboardCardProfiler::render(
            $cardname,
            $renderer
        );
    }

}