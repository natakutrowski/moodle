<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\dto\InboxThreadListResult;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxRenderer {

    /**
     * Renders the complete legacy Inbox view.
     *
     * Kept as a compatibility entry point while the Inbox is migrated
     * progressively to the generic CRM Workspace.
     */
    public static function render(
        InboxThreadListResult $result
    ): string {
        $out = html_writer::start_tag(
            'section',
            [
                'class' => 'crm-inbox',
                'aria-label' => get_string(
                    'crm_inbox_region_label',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= self::render_filters($result);
        $out .= self::render_thread_list($result);

        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Renders the Inbox search and filtering panel.
     */
    public static function render_filters(
        InboxThreadListResult $result
    ): string {
        $criteria = $result->criteria;

        $teamoptions = [
            '' => get_string(
                'all',
                'core'
            ),
        ];

        foreach ($result->teams as $team) {
            $teamoptions[(string)(int)$team->id] =
                (string)$team->name;
        }

        $accountoptions = [
            '' => get_string(
                'all',
                'core'
            ),
        ];

        foreach ($result->accounts as $account) {
            $accountoptions[
                (string)(int)$account->id
            ] =
                (string)$account->name
                . ' · '
                . (string)$account->email;
        }

        $checkboxattributes = [
            'type' => 'checkbox',
            'name' => 'unreadonly',
            'id' => 'id_unreadonly',
            'value' => '1',
            'class' => 'form-check-input',
        ];

        if ($criteria->unreadonly) {
            $checkboxattributes['checked'] =
                'checked';
        }

        $folderlabels = [
            'inbox' => ['fa-inbox', 'crm_inbox_folder_inbox_o13'],
            'sent' => ['fa-paper-plane', 'crm_inbox_folder_sent_o13'],
            'drafts' => ['fa-pencil', 'crm_inbox_folder_drafts_o13'],
            'archive' => ['fa-archive', 'crm_inbox_folder_archive_o13'],
            'trash' => ['fa-trash', 'crm_inbox_folder_trash_o13'],
            'all' => ['fa-envelope', 'crm_inbox_folder_all_o13'],
        ];

        $foldernav = '';

        foreach ($folderlabels as $folder => $definition) {
            $foldercriteria =
                $criteria->with_folder($folder);

            $classes = 'crm-inbox-folder-link';

            if ($criteria->folder === $folder) {
                $classes .= ' is-active';
            }

            $foldernav .= html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_page(),
                    $foldercriteria->url_params(false)
                ),
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            'fa ' . $definition[0],
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        $definition[1],
                        'local_subscriptions'
                    ),
                    'crm-inbox-folder-label'
                )
                . html_writer::span(
                    (string)(
                        $result->foldercounts[$folder]
                        ?? 0
                    ),
                    'crm-inbox-folder-count'
                ),
                [
                    'class' => $classes,
                    'aria-current' =>
                        $criteria->folder === $folder
                            ? 'page'
                            : null,
                ]
            );
        }

        $refresh = '';

        if (
            AdminSecurity::can(
                Capabilities::MANAGE_INBOX
            )
        ) {
            $refresh = html_writer::tag(
                'form',
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'hidden',
                        'name' => 'sesskey',
                        'value' => sesskey(),
                    ]
                )
                . html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'hidden',
                        'name' => 'returnurl',
                        'value' => (
                            new moodle_url(
                                subscription_config::
                                    admin_inbox_page(),
                                $criteria->url_params()
                            )
                        )->out(false),
                    ]
                )
                . html_writer::tag(
                    'button',
                    html_writer::tag(
                        'i',
                        '',
                        [
                            'class' => 'fa fa-refresh',
                            'aria-hidden' => 'true',
                        ]
                    )
                    . html_writer::span(
                        get_string(
                            'crm_inbox_refresh',
                            'local_subscriptions'
                        )
                    ),
                    [
                        'type' => 'submit',
                        'class' =>
                            'crm-inbox-folder-link '
                            . 'crm-inbox-folder-refresh',
                    ]
                ),
                [
                    'method' => 'post',
                    'action' => (
                        new moodle_url(
                            subscription_config::
                                admin_inbox_sync_page()
                        )
                    )->out(false),
                    'class' =>
                        'crm-inbox-folder-refresh-form',
                ]
            );
        }

        $lastsync = '';
        $lastsyncedat = 0;

        foreach ((new InboxAccountRepository())->get_enabled() as $account) {
            $lastsyncedat = max(
                $lastsyncedat,
                (int)($account->lastsyncedat ?? 0)
            );
        }

        if ($lastsyncedat > 0) {
            $lastsync = html_writer::span(
                get_string(
                    'crm_inbox_last_sync_o1634',
                    'local_subscriptions',
                    userdate(
                        $lastsyncedat,
                        get_string('strftimedatetimeshort', 'langconfig')
                    )
                ),
                'crm-inbox-last-sync'
            );
        }

        $out = html_writer::div(
            $foldernav . $refresh . $lastsync,
            'crm-inbox-folder-navigation mb-3',
            [
                'aria-label' => get_string(
                    'crm_inbox_folder_navigation_o13',
                    'local_subscriptions'
                ),
            ]
        );

        $quickparams =
            $criteria->url_params(false);

        unset(
            $quickparams['period'],
            $quickparams['datefrom'],
            $quickparams['dateto'],
            $quickparams['page']
        );

        $quickhidden = '';

        foreach ($quickparams as $name => $value) {
            if (
                !is_scalar($value)
                || (string)$value === ''
            ) {
                continue;
            }

            $quickhidden .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => (string)$name,
                    'value' => (string)$value,
                ]
            );
        }

        $quickperiod = html_writer::tag(
            'form',
            $quickhidden
            . html_writer::label(
                get_string(
                    'crm_inbox_period_filter_o11',
                    'local_subscriptions'
                ),
                'crm-inbox-o16-period',
                false,
                [
                    'class' =>
                        'crm-inbox-o16-period-label',
                ]
            )
            . html_writer::select(
                [
                    '' => get_string(
                        'all',
                        'core'
                    ),
                    'today' => get_string(
                        'today',
                        'core'
                    ),
                    '7days' => get_string(
                        'crm_inbox_period_7days_o11',
                        'local_subscriptions'
                    ),
                    '30days' => get_string(
                        'crm_inbox_period_30days_o11',
                        'local_subscriptions'
                    ),
                    '90days' => get_string(
                        'crm_inbox_period_90days_o11',
                        'local_subscriptions'
                    ),
                    'custom' => get_string(
                        'crm_inbox_period_custom_o16_3_2',
                        'local_subscriptions'
                    ),
                ],
                'period',
                $criteria->period,
                false,
                [
                    'id' =>
                        'crm-inbox-o16-period',
                    'class' =>
                        'form-select form-select-sm '
                        . 'crm-inbox-o16-period-select',
                ]
            )
            . html_writer::div(
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'date',
                        'name' => 'datefrom',
                        'value' => $criteria->datefrom,
                        'class' => 'form-control form-control-sm',
                        'title' => get_string(
                            'crm_inbox_period_from_o16_3_2',
                            'local_subscriptions'
                        ),
                        'aria-label' => get_string(
                            'crm_inbox_period_from_o16_3_2',
                            'local_subscriptions'
                        ),
                    ]
                ),
                'crm-inbox-custom-period-field'
                . ($criteria->period === 'custom' ? '' : ' d-none'),
                ['data-inbox-custom-period-field' => '1']
            )
            . html_writer::div(
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'date',
                        'name' => 'dateto',
                        'value' => $criteria->dateto,
                        'class' => 'form-control form-control-sm',
                        'title' => get_string(
                            'crm_inbox_period_to_o16_3_2',
                            'local_subscriptions'
                        ),
                        'aria-label' => get_string(
                            'crm_inbox_period_to_o16_3_2',
                            'local_subscriptions'
                        ),
                    ]
                ),
                'crm-inbox-custom-period-field'
                . ($criteria->period === 'custom' ? '' : ' d-none'),
                ['data-inbox-custom-period-field' => '1']
            )
            . html_writer::tag(
                'button',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            'fa fa-calendar-check-o',
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'crm_inbox_o16_2_period_apply',
                        'local_subscriptions'
                    ),
                    'visually-hidden'
                ),
                [
                    'type' => 'submit',
                    'class' =>
                        'btn btn-outline-secondary '
                        . 'btn-sm crm-inbox-o16-period-apply',
                    'title' => get_string(
                        'crm_inbox_o16_2_period_apply',
                        'local_subscriptions'
                    ),
                    'aria-label' => get_string(
                        'crm_inbox_o16_2_period_apply',
                        'local_subscriptions'
                    ),
                ]
            ),
            [
                'method' => 'get',
                'action' => (
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page()
                    )
                )->out(false),
                'class' =>
                    'crm-inbox-o16-period-form',
            ]
        );

        $out .= html_writer::start_div(
            'crm-inbox-o16-filter-row'
        );

        $out .= $quickperiod;

        $filtercount =
            $criteria->active_filter_count();

        $detailsattributes = [
            'class' =>
                'crm-inbox-filter-details mb-3',
        ];

        if ($filtercount > 0) {
            $detailsattributes['open'] = 'open';
        }

        $summarylabel = get_string(
            'crm_inbox_o16_1_filters_summary',
            'local_subscriptions'
        );

        if ($filtercount > 0) {
            $summarylabel .= ' · ' . get_string(
                'crm_inbox_active_filters_o11',
                'local_subscriptions',
                $filtercount
            );
        }

        $out .= html_writer::start_tag(
            'details',
            $detailsattributes
        );

        $out .= html_writer::tag(
            'summary',
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'fa fa-filter',
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                $summarylabel
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_o16_1_filters_hint',
                    'local_subscriptions'
                ),
                'crm-inbox-filter-summary-hint'
            ),
            [
                'class' =>
                    'crm-inbox-filter-details-summary',
            ]
        );

        $out .= html_writer::start_tag(
            'form',
            [
                'method' => 'get',

                'action' =>
                    subscription_config::
                        admin_inbox_page(),
                'class' =>
                    'crm-inbox-filters card card-body',
                'aria-label' => get_string(
                    'crm_inbox_filters_label',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'folder',
                'value' => $criteria->folder,
            ]
        );

        $out .= html_writer::start_div(
            'row g-2 align-items-end'
        );

        $out .= self::input(
            'q',
            get_string(
                'crm_inbox_search',
                'local_subscriptions'
            ),
            $criteria->query,
            'col-12 col-xl-5',
            get_string(
                'crm_inbox_search_help_o11',
                'local_subscriptions'
            )
        );

        $out .= self::select(
            'status',
            get_string(
                'crm_inbox_status',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'open' => get_string(
                    'crm_inbox_status_open',
                    'local_subscriptions'
                ),
                'pending' => get_string(
                    'crm_inbox_status_pending',
                    'local_subscriptions'
                ),
                'resolved' => get_string(
                    'crm_inbox_status_resolved',
                    'local_subscriptions'
                ),
                'closed' => get_string(
                    'crm_inbox_status_closed',
                    'local_subscriptions'
                ),
                'spam' => get_string(
                    'crm_inbox_status_spam',
                    'local_subscriptions'
                ),
            ],
            $criteria->status,
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'priority',
            get_string(
                'crm_inbox_priority',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'low' => get_string(
                    'crm_inbox_priority_low',
                    'local_subscriptions'
                ),
                'normal' => get_string(
                    'crm_inbox_priority_normal',
                    'local_subscriptions'
                ),
                'high' => get_string(
                    'crm_inbox_priority_high',
                    'local_subscriptions'
                ),
                'urgent' => get_string(
                    'crm_inbox_priority_urgent',
                    'local_subscriptions'
                ),
            ],
            $criteria->priority,
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'assignment',
            get_string(
                'crm_inbox_assignment',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'mine' => get_string(
                    'crm_inbox_assignment_mine',
                    'local_subscriptions'
                ),
                'unassigned' => get_string(
                    'crm_inbox_assignment_unassigned',
                    'local_subscriptions'
                ),
                'team' => get_string(
                    'crm_inbox_assignment_team',
                    'local_subscriptions'
                ),
            ],
            $criteria->assignment,
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'match',
            get_string(
                'crm_inbox_match_status',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'matched' => get_string(
                    'crm_inbox_match_matched',
                    'local_subscriptions'
                ),
                'unmatched' => get_string(
                    'crm_inbox_match_unmatched',
                    'local_subscriptions'
                ),
                'ambiguous' => get_string(
                    'crm_inbox_match_ambiguous',
                    'local_subscriptions'
                ),
            ],
            $criteria->match,
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'direction',
            get_string(
                'crm_inbox_direction_filter_o10',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'inbound' => get_string(
                    'crm_inbox_direction_received_o10',
                    'local_subscriptions'
                ),
                'outbound' => get_string(
                    'crm_inbox_direction_sent_o10',
                    'local_subscriptions'
                ),
                'draft' => get_string(
                    'crm_inbox_message_status_draft',
                    'local_subscriptions'
                ),
            ],
            $criteria->direction,
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'accountid',
            get_string(
                'crm_inbox_account_filter_o11',
                'local_subscriptions'
            ),
            $accountoptions,
            $criteria->accountid > 0
                ? (string)$criteria->accountid
                : '',
            'col-12 col-md-6 col-xl-3'
        );

        $out .= self::select(
            'readstate',
            get_string(
                'crm_inbox_read_state_o11',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'unread' => get_string(
                    'crm_inbox_read_state_unread_o11',
                    'local_subscriptions'
                ),
                'read' => get_string(
                    'crm_inbox_read_state_read_o11',
                    'local_subscriptions'
                ),
            ],
            $criteria->readstate,
            'col-6 col-xl-2'
        );

        $out .= self::select(
            'attachments',
            get_string(
                'crm_inbox_attachments_filter_o11',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'with' => get_string(
                    'crm_inbox_attachments_with_o11',
                    'local_subscriptions'
                ),
                'without' => get_string(
                    'crm_inbox_attachments_without_o11',
                    'local_subscriptions'
                ),
            ],
            $criteria->attachmentstate,
            'col-6 col-xl-2'
        );

        $out .= self::select(
            'period',
            get_string(
                'crm_inbox_period_filter_o11',
                'local_subscriptions'
            ),
            [
                '' => get_string('all', 'core'),
                'today' => get_string(
                    'today',
                    'core'
                ),
                '7days' => get_string(
                    'crm_inbox_period_7days_o11',
                    'local_subscriptions'
                ),
                '30days' => get_string(
                    'crm_inbox_period_30days_o11',
                    'local_subscriptions'
                ),
                '90days' => get_string(
                    'crm_inbox_period_90days_o11',
                    'local_subscriptions'
                ),
                'custom' => get_string(
                    'crm_inbox_period_custom_o16_3_2',
                    'local_subscriptions'
                ),
            ],
            $criteria->period,
            'col-6 col-xl-2'
        );

        $customdateclass =
            'col-6 col-xl-2 crm-inbox-custom-period-field'
            . ($criteria->period === 'custom' ? '' : ' d-none');

        $out .= self::date_input(
            'datefrom',
            get_string(
                'crm_inbox_period_from_o16_3_2',
                'local_subscriptions'
            ),
            $criteria->datefrom,
            $customdateclass
        );

        $out .= self::date_input(
            'dateto',
            get_string(
                'crm_inbox_period_to_o16_3_2',
                'local_subscriptions'
            ),
            $criteria->dateto,
            $customdateclass
        );

        $out .= self::select(
            'teamid',
            get_string(
                'crm_inbox_team',
                'local_subscriptions'
            ),
            $teamoptions,
            $criteria->teamid > 0
                ? (string)$criteria->teamid
                : '',
            'col-6 col-lg-2'
        );

        $out .= self::select(
            'perpage',
            get_string(
                'crm_inbox_per_page',
                'local_subscriptions'
            ),
            [
                '25' => '25',
                '50' => '50',
                '100' => '100',
            ],
            (string)$criteria->perpage,
            'col-6 col-lg-2'
        );

        $out .= html_writer::div(
            html_writer::div(
                html_writer::empty_tag(
                    'input',
                    $checkboxattributes
                ) .
                html_writer::label(
                    get_string(
                        'crm_inbox_unread_only',
                        'local_subscriptions'
                    ),
                    'id_unreadonly',
                    false,
                    [
                        'class' =>
                            'form-check-label ms-2',
                    ]
                ),
                'form-check'
            ),
            'col-12 col-lg-2 d-flex align-items-center pb-lg-2'
        );

        $out .= html_writer::div(
            html_writer::tag(
                'button',
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            'fa fa-filter me-1',
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'crm_inbox_o16_2_apply',
                        'local_subscriptions'
                    )
                ),
                [
                    'type' => 'submit',
                    'class' =>
                        'btn btn-primary '
                        . 'crm-inbox-filter-apply',
                ]
            ),
            'col-auto crm-inbox-filter-action-col'
        );

        $out .= html_writer::div(
            html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' =>
                            'fa fa-undo me-1',
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'reset',
                        'core'
                    )
                ),
                [
                    'class' =>
                        'btn btn-outline-secondary '
                        . 'crm-inbox-filter-reset',
                ]
            ),
            'col-auto crm-inbox-filter-action-col'
        );

        $out .= html_writer::end_div();

        if ($criteria->active_filter_count() > 0) {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_active_filters_o11',
                    'local_subscriptions',
                    $criteria->active_filter_count()
                )
                . ' · '
                . html_writer::link(
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page()
                    ),
                    get_string(
                        'crm_inbox_clear_filters_o11',
                        'local_subscriptions'
                    )
                ),
                'crm-inbox-filter-summary mt-2'
            );
        }

        $out .= html_writer::end_tag('form');
        $out .= html_writer::end_tag('details');
        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Renders the Inbox result summary, thread list and pagination.
     */
    public static function render_thread_list(
        InboxThreadListResult $result
    ): string {
        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'crm-inbox-thread-list-panel',
                'aria-label' => get_string(
                    'crm_inbox_thread_list_label',
                    'local_subscriptions'
                ),
            ]
        );

        $canmanage =
            AdminSecurity::can(
                Capabilities::MANAGE_INBOX
            );

        if ($canmanage && $result->has_results()) {
            $out .= html_writer::start_tag(
                'form',
                [
                    'method' => 'post',
                    'action' => (
                        new moodle_url(
                            subscription_config::
                                admin_inbox_bulk_action_page()
                        )
                    )->out(false),
                    'class' =>
                        'crm-inbox-bulk-form',
                    'data-bulk-trash-confirm' =>
                        get_string(
                            'crm_inbox_bulk_trash_confirm_o12',
                            'local_subscriptions'
                        ),
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'sesskey',
                    'value' => sesskey(),
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'returnurl',
                    'value' => (
                        new moodle_url(
                            subscription_config::
                                admin_inbox_page(),
                            $result->criteria
                                ->url_params()
                        )
                    )->out(false),
                ]
            );

            $bulkoptions = [
                '' => get_string(
                    'crm_inbox_bulk_choose_action_o12',
                    'local_subscriptions'
                ),
                'read' => get_string(
                    'crm_inbox_mark_read_o2',
                    'local_subscriptions'
                ),
                'unread' => get_string(
                    'crm_inbox_mark_unread_o2',
                    'local_subscriptions'
                ),
                'archive' => get_string(
                    'crm_inbox_bulk_archive_o12',
                    'local_subscriptions'
                ),
                'trash' => get_string(
                    'crm_inbox_bulk_trash_o12',
                    'local_subscriptions'
                ),
                'restore' => get_string(
                    'crm_inbox_restore_to_inbox_o13',
                    'local_subscriptions'
                ),
                'status_open' => get_string(
                    'crm_inbox_bulk_status_open_o12',
                    'local_subscriptions'
                ),
                'status_pending' => get_string(
                    'crm_inbox_bulk_status_pending_o12',
                    'local_subscriptions'
                ),
                'status_resolved' => get_string(
                    'crm_inbox_bulk_status_resolved_o12',
                    'local_subscriptions'
                ),
                'status_closed' => get_string(
                    'crm_inbox_bulk_status_closed_o12',
                    'local_subscriptions'
                ),
                'status_spam' => get_string(
                    'crm_inbox_bulk_status_spam_o12',
                    'local_subscriptions'
                ),
                'priority_low' => get_string(
                    'crm_inbox_bulk_priority_low_o12',
                    'local_subscriptions'
                ),
                'priority_normal' => get_string(
                    'crm_inbox_bulk_priority_normal_o12',
                    'local_subscriptions'
                ),
                'priority_high' => get_string(
                    'crm_inbox_bulk_priority_high_o12',
                    'local_subscriptions'
                ),
                'priority_urgent' => get_string(
                    'crm_inbox_bulk_priority_urgent_o12',
                    'local_subscriptions'
                ),
            ];

            $out .= html_writer::div(
                html_writer::div(
                    html_writer::empty_tag(
                        'input',
                        [
                            'type' => 'checkbox',
                            'id' =>
                                'crm-inbox-select-all',
                            'data-inbox-select-all' =>
                                '1',
                        ]
                    )
                    . html_writer::label(
                        get_string(
                            'crm_inbox_bulk_select_all_o12',
                            'local_subscriptions'
                        ),
                        'crm-inbox-select-all',
                        false,
                        ['class' => 'mb-0 ms-2']
                    ),
                    'crm-inbox-bulk-select-all'
                )
                . html_writer::select(
                    $bulkoptions,
                    'action',
                    '',
                    false,
                    [
                        'class' =>
                            'form-select form-select-sm '
                            . 'crm-inbox-bulk-action-select',
                        'required' => 'required',
                        'data-inbox-bulk-action-select' =>
                            '1',
                    ]
                )
                . html_writer::tag(
                    'button',
                    get_string(
                        'crm_inbox_bulk_apply_o12',
                        'local_subscriptions'
                    ),
                    [
                        'type' => 'submit',
                        'class' =>
                            'btn btn-sm btn-primary',
                        'data-inbox-bulk-apply' =>
                            '1',
                        'disabled' => 'disabled',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'crm_inbox_bulk_selected_count_o12',
                        'local_subscriptions',
                        0
                    ),
                    'crm-inbox-bulk-count',
                    [
                        'data-inbox-bulk-count' =>
                            '1',
                    ]
                ),
                'crm-inbox-bulk-toolbar'
            );
        }

        $out .= html_writer::div(
            get_string(
                'crm_inbox_result_count',
                'local_subscriptions',
                $result->total
            ),
            'crm-inbox-result-summary',
            [
                'role' => 'status',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
                'data-inbox-live-region' => '1',
            ]
        );

        if (!$result->has_results()) {
            $out .= html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string(
                        'crm_inbox_empty_title',
                        'local_subscriptions'
                    ),
                    [
                        'class' => 'd-block mb-1',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'crm_inbox_empty',
                        'local_subscriptions'
                    )
                ),
                'alert alert-light border crm-inbox-empty-state',
                [
                    'role' => 'status',
                ]
            );

            $out .= html_writer::end_tag(
                'section'
            );

            return $out;
        }

        $out .= html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-thread-list',

                'role' => 'list',

                'aria-label' => get_string(
                    'crm_inbox_thread_list_label',
                    'local_subscriptions'
                ),
            ]
        );

        foreach ($result->threads as $thread) {
            $out .= self::thread_card($thread, $canmanage);
        }

        $out .= html_writer::end_div();

        $baseurl = new moodle_url(
            subscription_config::admin_inbox_page(),
            $result->criteria->url_params(false)
        );

        $out .= $GLOBALS['OUTPUT']->paging_bar(
            $result->total,
            $result->criteria->page,
            $result->criteria->perpage,
            $baseurl
        );

        if ($canmanage && $result->has_results()) {
            $out .= html_writer::end_tag(
                'form'
            );
        }

        $out .= html_writer::end_tag(
            'section'
        );

        return $out;
    }

    private static function thread_card(
        object $thread,
        bool $canmanage = false
    ): string {
        $contact = trim(
            (string)($thread->contactname ?? '')
        );

        if ($contact === '') {
            $contact = (string)(
                $thread->contactemail
                ?? get_string(
                    'crm_inbox_unknown_contact',
                    'local_subscriptions'
                )
            );
        }

        $snippet = trim(
            strip_tags(
                (string)(
                    $thread->lastbodytext
                    ?: $thread->lastbodyhtml
                    ?: ''
                )
            )
        );

        $snippet = shorten_text(
            $snippet,
            180
        );

        $classes = [
            'crm-inbox-thread-card',
            'card',
            'mb-2',
        ];

        if ((int)$thread->unreadcount > 0) {
            $classes[] =
                'crm-inbox-thread-card-unread';
        }

        $threadid =
            (int)$thread->id;

        $iscomposedraft = strtoupper(
            trim(
                (string)($thread->folder ?? '')
            )
        ) === 'DRAFTS';

        $threadurl = $iscomposedraft
            ? new moodle_url(
                subscription_config::
                    admin_inbox_compose_page(),
                [
                    'threadid' => $threadid,
                ]
            )
            : new moodle_url(
                subscription_config::
                    admin_inbox_thread_page(),
                [
                    'id' => $threadid,
                ]
            );

        $previewurl = new moodle_url(
            subscription_config::
                ajax_inbox_thread_preview_page()
        );            

        $threadtitleid =
            'crm-inbox-thread-title-' .
            $threadid;

        $out = html_writer::start_tag(
            'article',
            [
                'class' =>
                    implode(' ', $classes),

                'role' => 'listitem',

                'aria-labelledby' =>
                    $threadtitleid,

                'data-inbox-thread-card' => '1',

                'data-thread-id' =>
                    $threadid,

                'data-unread-count' =>
                    (int)($thread->unreadcount ?? 0),

                'data-preview-url' =>
                    $previewurl->out(false),
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        if ($canmanage) {
            $checkboxid =
                'crm-inbox-thread-select-' .
                $threadid;

            $out .= html_writer::div(
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'checkbox',
                        'name' => 'threadids[]',
                        'value' => $threadid,
                        'id' => $checkboxid,
                        'class' =>
                            'form-check-input ' .
                            'crm-inbox-thread-select',
                        'data-inbox-thread-select' =>
                            '1',
                        'aria-label' => get_string(
                            'crm_inbox_select_thread_o2',
                            'local_subscriptions',
                            (string)(
                                trim((string)$thread->subject) !== ''
                                    ? $thread->subject
                                    : get_string(
                                        'crm_inbox_no_subject',
                                        'local_subscriptions'
                                    )
                            )
                        ),
                    ]
                ),
                'crm-inbox-thread-select-wrap'
            );
        }

        $out .= html_writer::start_div(
            'd-flex justify-content-between gap-2 '
            . 'crm-inbox-thread-card-layout'
        );

        $out .= html_writer::start_div(
            'flex-grow-1 min-w-0 '
            . 'crm-inbox-thread-card-main'
        );

        $out .= html_writer::div(
            self::thread_direction_badge(
                $thread
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_status_' .
                        (string)$thread->status,
                    'local_subscriptions'
                ),
                'crm-inbox-thread-state-label'
            ),
            'crm-inbox-thread-semantics'
        );

        $out .= html_writer::link(
            $threadurl,
            s(
                trim((string)$thread->subject) !== ''
                    ? (string)$thread->subject
                    : get_string(
                        'crm_inbox_no_subject',
                        'local_subscriptions'
                    )
            ),
            [
                'id' => $threadtitleid,

                'class' =>
                    'h5 d-block mb-1 ' .
                    'text-decoration-none ' .
                    'crm-inbox-thread-title',

                'data-inbox-thread-preview' => '1',

                'data-thread-id' =>
                    $threadid,

                'aria-controls' =>
                    'crm-inbox-preview-regions',
            ]
        );

        $out .= html_writer::div(
            s($contact) .
            ' · ' .
            s((string)($thread->contactemail ?? '')),
            'text-muted small mb-2'
        );

        if ($snippet !== '') {
            $out .= html_writer::div(
                s($snippet),
                'crm-inbox-thread-snippet'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'text-end flex-shrink-0 '
            . 'crm-inbox-thread-card-meta'
        );

        $out .= html_writer::start_div(
            'crm-inbox-thread-badges'
        );

        $out .= html_writer::span(
            get_string(
                'crm_inbox_priority_' .
                    $thread->priority,
                'local_subscriptions'
            ),
            'badge crm-inbox-priority-badge ' .
            self::priority_class(
                (string)$thread->priority
            )
        );

        if ((int)$thread->unreadcount > 0) {
            $out .= html_writer::span(
                get_string(
                    'crm_inbox_unread_count_compact',
                    'local_subscriptions',
                    (int)$thread->unreadcount
                ),
                'badge bg-primary crm-inbox-unread-badge',
                [
                    'aria-label' => get_string(
                        'crm_inbox_unread_count_accessible',
                        'local_subscriptions',
                        (int)$thread->unreadcount
                    ),
                ]
            );
        }

        $out .= html_writer::end_div();

        if (!empty($thread->lastmessageat)) {
            $out .= html_writer::div(
                userdate(
                    (int)$thread->lastmessageat,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                ),
                'small text-muted mt-2'
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        $out .= html_writer::end_tag(
            'article'
        );

        return $out;
    }

    private static function thread_direction_badge(
        object $thread
    ): string {
        $isdraft =
            (string)($thread->lastmessagestatus ?? '') ===
                'draft'
            || strtoupper(
                trim(
                    (string)($thread->folder ?? '')
                )
            ) === 'DRAFTS';

        if ($isdraft) {
            return html_writer::span(
                get_string(
                    'crm_inbox_message_status_draft',
                    'local_subscriptions'
                ),
                'badge crm-inbox-direction-badge '
                . 'crm-inbox-direction-badge-draft'
            );
        }

        $direction = (string)(
            $thread->lastdirection ?? ''
        );

        if ($direction === 'outbound') {
            return html_writer::span(
                get_string(
                    'crm_inbox_direction_sent_o10',
                    'local_subscriptions'
                ),
                'badge crm-inbox-direction-badge '
                . 'crm-inbox-direction-badge-sent'
            );
        }

        return html_writer::span(
            get_string(
                'crm_inbox_direction_received_o10',
                'local_subscriptions'
            ),
            'badge crm-inbox-direction-badge '
            . 'crm-inbox-direction-badge-received'
        );
    }

    private static function priority_class(
        string $priority
    ): string {
        return match ($priority) {
            'urgent' =>
                'bg-danger',

            'high' =>
                'bg-warning text-dark',

            'low' =>
                'bg-light text-dark border',

            default =>
                'bg-secondary',
        };
    }

    private static function date_input(
        string $name,
        string $label,
        string $value,
        string $columnclass
    ): string {
        return html_writer::div(
            html_writer::label(
                $label,
                'id_' . $name,
                false,
                ['class' => 'form-label']
            )
            . html_writer::empty_tag(
                'input',
                [
                    'type' => 'date',
                    'name' => $name,
                    'id' => 'id_' . $name,
                    'value' => $value,
                    'class' => 'form-control',
                ]
            ),
            $columnclass,
            ['data-inbox-custom-period-field' => '1']
        );
    }

    private static function input(
        string $name,
        string $label,
        string $value,
        string $class,
        string $placeholder = ''
    ): string {
        return html_writer::div(
            html_writer::label(
                $label,
                'id_' . $name,
                false,
                ['class' => 'form-label']
            ) .
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'text',
                    'name' => $name,
                    'id' => 'id_' . $name,
                    'value' => $value,
                    'class' => 'form-control',
                    'placeholder' =>
                        $placeholder,
                    'autocomplete' => 'off',
                    'enterkeyhint' => 'search',
                ]
            ),
            $class
        );
    }

    private static function select(
        string $name,
        string $label,
        array $options,
        string $selected,
        string $class
    ): string {
        return html_writer::div(
            html_writer::label(
                $label,
                'id_' . $name,
                false,
                ['class' => 'form-label']
            ) .
            html_writer::select(
                $options,
                $name,
                $selected,
                false,
                ['class' => 'form-select']
            ),
            $class
        );
    }
}