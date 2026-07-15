<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\crm\intelligence\dashboard\CrmIntelligenceDashboardBuilder;
use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmIntelligenceCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        $canviewinbox =
            Capabilities::can_view_inbox();

        $overview =
            (
                new
                    CrmIntelligenceDashboardBuilder()
            )->build(
                CrmIntelligenceLimits::
                    DASHBOARD_USERS,
                $canviewinbox
            );

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

                $profilecontent =
                    html_writer::link(
                        $url,
                        s(fullname($user)),
                        [
                            'class' => 'fw-bold',
                        ]
                    );

                $profilecontent .= html_writer::div(
                    s($user->email),
                    'text-muted small'
                );

                $profilecontent .= html_writer::div(
                    get_string(
                        'crm_intelligence_global_score',
                        'local_subscriptions'
                    ) .
                    ': ' .
                    $score->global() .
                    '/100',
                    'small mt-1'
                );

                if (
                    $canviewinbox &&
                    !empty($profile->inbox)
                ) {
                    $profilecontent .=
                        self::render_inbox_summary(
                            $profile->user,
                            $profile->inbox
                        );
                }

                $out .= html_writer::div(
                    $profilecontent,
                    'border rounded p-2 mb-2'
                );
            }
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }

    private static function render_inbox_summary(
        \stdClass $user,
        \stdClass $inbox
    ): string {
        $conversationcount = (int)(
            $inbox->conversationcount
            ?? 0
        );

        if ($conversationcount <= 0) {
            return '';
        }

        $opencount = (int)(
            $inbox->openconversationcount
            ?? 0
        );

        $unreadcount = (int)(
            $inbox->unreadcount
            ?? 0
        );

        $urgentcount = (int)(
            $inbox->urgentcount
            ?? 0
        );

        $badges = html_writer::span(
            get_string(
                'crm_intelligence_inbox_conversations',
                'local_subscriptions',
                $conversationcount
            ),
            'badge bg-light text-dark border'
        );

        if ($opencount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_intelligence_inbox_open',
                    'local_subscriptions',
                    $opencount
                ),
                'badge bg-primary'
            );
        }

        if ($unreadcount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_intelligence_inbox_unread',
                    'local_subscriptions',
                    $unreadcount
                ),
                'badge bg-danger'
            );
        }

        if ($urgentcount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_intelligence_inbox_urgent',
                    'local_subscriptions',
                    $urgentcount
                ),
                'badge bg-warning text-dark'
            );
        }

        $inboxurl = new moodle_url(
            subscription_config::
                admin_inbox_page(),
            [
                'q' => (string)$user->email,
            ]
        );

        return html_writer::div(
            html_writer::div(
                $badges,
                'crm-intelligence-profile-inbox-badges'
            ) .
            html_writer::link(
                $inboxurl,
                get_string(
                    'crm_intelligence_inbox_open_link',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-intelligence-profile-inbox-link',
                ]
            ),
            'crm-intelligence-profile-inbox mt-2'
        );
    }

}