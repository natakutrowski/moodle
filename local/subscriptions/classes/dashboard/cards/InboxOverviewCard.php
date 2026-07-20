<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
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

        $content = DashboardCardUi::header(
            title: get_string(
                'dashboard_inbox_title',
                'local_subscriptions'
            ),
            icon: '✉️',
            subtitle: get_string(
                'dashboard_inbox_subtitle',
                'local_subscriptions'
            ),
            actions: DashboardCardUi::action(
                $inboxurl,
                get_string(
                    'dashboard_inbox_open',
                    'local_subscriptions'
                )
            ),
            titleid:
                'crm-dashboard-inbox-title'
        );

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

        $content .= html_writer::start_div(
            'crm-dashboard-inbox-metrics'
        );

        foreach ($metrics as $metric) {
            $content .= html_writer::link(
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

        $content .= html_writer::end_div();

        $content .= html_writer::tag(
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
            $content .= DashboardCardUi::empty_state(
                title: get_string(
                    'dashboard_inbox_empty',
                    'local_subscriptions'
                ),
                icon: '✓',
                tone:
                    DashboardCardUi::TONE_SUCCESS
            );
        } else {
            $content .= html_writer::start_tag(
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

                $content .= html_writer::tag(
                    'li',
                    $item,
                    [
                        'class' =>
                            'crm-dashboard-inbox-' .
                            'activity-item',
                    ]
                );
            }

            $content .= html_writer::end_tag('ul');
        }

        return DashboardCardUi::shell(
            content: $content,
            extraclasses:
                'crm-dashboard-inbox-card',
            labelledby:
                'crm-dashboard-inbox-title'
        );
    }
}