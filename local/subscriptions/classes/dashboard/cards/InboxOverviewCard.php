<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\inbox\DashboardInboxRepository;
use local_subscriptions\dashboard\inbox\DashboardInboxService;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxOverviewCard implements DashboardCard {

    public static function render(): string {
        if (!Capabilities::can_view_inbox()) {
            return '';
        }

        $summary = (
            new DashboardInboxService(
                new DashboardInboxRepository()
            )
        )->load();

        if (!$summary->available) {
            return '';
        }

        $inboxurl = new moodle_url(
            subscription_config::admin_inbox_page()
        );

        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'card card-body ' .
                    'local-subscriptions-dashboard-card ' .
                    'crm-dashboard-panel ' .
                    'crm-dashboard-inbox-card',

                'aria-labelledby' =>
                    'crm-dashboard-inbox-title',
            ]
        );

        $out .= html_writer::start_tag(
            'header',
            [
                'class' =>
                    'crm-dashboard-panel-header',
            ]
        );

        $out .= html_writer::start_div(
            'crm-dashboard-panel-heading'
        );

        $out .= html_writer::tag(
            'h3',
            get_string(
                'dashboard_inbox_title',
                'local_subscriptions'
            ),
            [
                'id' =>
                    'crm-dashboard-inbox-title',

                'class' =>
                    'crm-dashboard-panel-title',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'dashboard_inbox_subtitle',
                'local_subscriptions'
            ),
            'crm-dashboard-panel-subtitle'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::link(
            $inboxurl,
            get_string(
                'dashboard_inbox_open',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-primary ' .
                    'crm-dashboard-panel-action',
            ]
        );

        $out .= html_writer::end_tag('header');

        $metrics = [
            [
                'count' =>
                    $summary->opencount,

                'label' =>
                    get_string(
                        'dashboard_inbox_open_conversations',
                        'local_subscriptions'
                    ),

                'url' =>
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page(),
                        [
                            'status' => 'open',
                        ]
                    ),
            ],
            [
                'count' =>
                    $summary->unassignedcount,

                'label' =>
                    get_string(
                        'dashboard_inbox_unassigned',
                        'local_subscriptions'
                    ),

                'url' =>
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page(),
                        [
                            'assignment' =>
                                'unassigned',
                        ]
                    ),
            ],
            [
                'count' =>
                    $summary->urgentcount,

                'label' =>
                    get_string(
                        'dashboard_inbox_urgent',
                        'local_subscriptions'
                    ),

                'url' =>
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page(),
                        [
                            'priority' => 'urgent',
                        ]
                    ),
            ],
            [
                'count' =>
                    $summary->pendingcount,

                'label' =>
                    get_string(
                        'dashboard_inbox_pending',
                        'local_subscriptions'
                    ),

                'url' =>
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page(),
                        [
                            'status' => 'pending',
                        ]
                    ),
            ],
        ];

        $out .= html_writer::start_div(
            'crm-dashboard-inbox-metrics'
        );

        foreach ($metrics as $metric) {
            $out .= html_writer::link(
                $metric['url'],

                html_writer::span(
                    (string)$metric['count'],
                    'crm-dashboard-inbox-metric-value'
                ) .

                html_writer::span(
                    s($metric['label']),
                    'crm-dashboard-inbox-metric-label'
                ),

                [
                    'class' =>
                        'crm-dashboard-inbox-metric',

                    'aria-label' =>
                        get_string(
                            'dashboard_inbox_metric_aria',
                            'local_subscriptions',
                            (object)[
                                'label' =>
                                    $metric['label'],

                                'count' =>
                                    $metric['count'],
                            ]
                        ),
                ]
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::tag(
            'h4',
            get_string(
                'dashboard_inbox_recent_activity',
                'local_subscriptions'
            ),
            [
                'class' => 'h6 mt-4 mb-2',
            ]
        );

        if (!$summary->has_activity()) {
            $out .= html_writer::div(
                get_string(
                    'dashboard_inbox_empty',
                    'local_subscriptions'
                ),
                'text-muted small'
            );
        } else {
            $out .= html_writer::start_tag(
                'ul',
                [
                    'class' =>
                        'list-unstyled ' .
                        'crm-dashboard-inbox-activity ' .
                        'mb-0',
                ]
            );

            foreach (
                $summary->recentthreads
                as $thread
            ) {
                $subject = trim(
                    (string)(
                        $thread->subject
                        ?? ''
                    )
                );

                if ($subject === '') {
                    $subject = get_string(
                        'crm_inbox_no_subject',
                        'local_subscriptions'
                    );
                }

                $contact = trim(
                    (string)(
                        $thread->contactname
                        ?: $thread->contactemail
                        ?: ''
                    )
                );

                $meta = [];

                if ($contact !== '') {
                    $meta[] = $contact;
                }

                if (!empty($thread->lastmessageat)) {
                    $meta[] = userdate(
                        (int)$thread->lastmessageat,
                        get_string(
                            'strftimedatetimeshort',
                            'langconfig'
                        )
                    );
                }

                $threadurl = new moodle_url(
                    subscription_config::
                        admin_inbox_thread_page(),
                    [
                        'id' => (int)$thread->id,
                    ]
                );

                $item = html_writer::link(
                    $threadurl,
                    format_string($subject),
                    [
                        'class' =>
                            'crm-dashboard-inbox-' .
                            'activity-subject',
                    ]
                );

                if ($meta) {
                    $item .= html_writer::div(
                        s(
                            implode(
                                ' · ',
                                $meta
                            )
                        ),
                        'text-muted small'
                    );
                }

                $out .= html_writer::tag(
                    'li',
                    $item,
                    [
                        'class' =>
                            'crm-dashboard-inbox-' .
                            'activity-item',
                    ]
                );
            }

            $out .= html_writer::end_tag('ul');
        }

        $out .= html_writer::end_tag('section');

        return $out;
    }
}