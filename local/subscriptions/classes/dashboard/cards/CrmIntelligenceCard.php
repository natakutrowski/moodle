<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\crm\intelligence\dashboard\CrmIntelligenceDashboardBuilder;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmIntelligenceCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        $overview = (new CrmIntelligenceDashboardBuilder())->build();

        $out = html_writer::tag('h3', '🧠 ' . get_string('crm_intelligence_dashboard_title', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        $out .= html_writer::start_div('row mb-3');

        $cards = [
            [
                get_string('crm_intelligence_dashboard_analysed_users', 'local_subscriptions'),
                $overview->analysedUsers,
                '',
            ],
            [
                get_string('crm_intelligence_dashboard_hot_leads', 'local_subscriptions'),
                $overview->hotLeads,
                'hot_lead',
            ],
            [
                get_string('crm_intelligence_dashboard_at_risk', 'local_subscriptions'),
                $overview->atRisk,
                'at_risk',
            ],
            [
                get_string('crm_intelligence_dashboard_vip', 'local_subscriptions'),
                $overview->vip,
                'vip',
            ],
            [
                get_string('crm_intelligence_dashboard_trial_opportunities', 'local_subscriptions'),
                $overview->trialOpportunities,
                'trial_to_purchase',
            ],
            [
                get_string('crm_intelligence_dashboard_upgrade_opportunities', 'local_subscriptions'),
                $overview->upgradeOpportunities,
                'upgrade_subscription',
            ],
        ];

        foreach ($cards as [$label, $value, $filter]) {
            $content =
                html_writer::div((string)$value, 'crm-stat-number') .
                html_writer::div(s($label), 'text-muted small');

            if ($filter !== '') {
                $url = new moodle_url(subscription_config::admin_users_page(), [
                    'intelligence' => $filter,
                ]);

                $content = html_writer::link($url, $content, [
                    'class' => 'crm-intelligence-metric-link',
                ]);
            }

            $out .= html_writer::div(
                html_writer::div(
                    $content,
                    'card card-body local-subscriptions-dashboard-card crm-intelligence-metric-card'
                ),
                'col-md-6 col-xl-4 mb-3'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::tag('h4', get_string('crm_intelligence_dashboard_priority_profiles', 'local_subscriptions'), [
            'class' => 'h5 mt-3 mb-2',
        ]);

        if (empty($overview->priorityProfiles)) {
            $out .= html_writer::div(
                get_string('crm_intelligence_dashboard_no_priority_profiles', 'local_subscriptions'),
                'text-muted'
            );
        } else {
            foreach ($overview->priorityProfiles as $profile) {
                $user = $profile->user;
                $score = $profile->intelligence->leadScore;

                $url = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $user->id]);

                $out .= html_writer::div(
                    html_writer::link($url, s(fullname($user)), ['class' => 'fw-bold']) .
                    html_writer::div(s($user->email), 'text-muted small') .
                    html_writer::div(
                        get_string('crm_intelligence_global_score', 'local_subscriptions') . ': ' . $score->global() . '/100',
                        'small mt-1'
                    ),
                    'border rounded p-2 mb-2'
                );
            }
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }
}