<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\inbox\dto\InboxThreadListResult;
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

        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'get',
                'action' =>
                    subscription_config::
                        admin_inbox_page(),
                'class' =>
                    'crm-inbox-filters card card-body mb-3',
                'aria-label' => get_string(
                    'crm_inbox_filters_label',
                    'local_subscriptions'
                ),
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
            'col-12 col-lg-4'
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
                get_string(
                    'filter',
                    'core'
                ),
                [
                    'type' => 'submit',
                    'class' =>
                        'btn btn-primary w-100',
                ]
            ),
            'col-6 col-lg-2'
        );

        $out .= html_writer::div(
            html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
                get_string(
                    'reset',
                    'core'
                ),
                [
                    'class' =>
                        'btn btn-outline-secondary w-100',
                ]
            ),
            'col-6 col-lg-2'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('form');

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
            $out .= self::thread_card($thread);
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

        $out .= html_writer::end_tag(
            'section'
        );

        return $out;
    }

    private static function thread_card(
        object $thread
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

        $threadurl = new moodle_url(
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

                'data-preview-url' =>
                    $previewurl->out(false),
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        $out .= html_writer::start_div(
            'd-flex justify-content-between gap-3'
        );

        $out .= html_writer::start_div(
            'flex-grow-1'
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
            'text-end flex-shrink-0'
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
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_unread_count',
                    'local_subscriptions',
                    (int)$thread->unreadcount
                ),
                'badge bg-primary',
                [
                    'aria-label' => get_string(
                        'crm_inbox_unread_count_accessible',
                        'local_subscriptions',
                        (int)$thread->unreadcount
                    ),
                ]
            );
        }

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

    private static function input(
        string $name,
        string $label,
        string $value,
        string $class
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