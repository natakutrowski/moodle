<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxThreadRenderer {

    /**
     * Renders the complete legacy thread view.
     *
     * Kept as a compatibility entry point while the thread page
     * is migrated to the generic CRM Workspace.
     */
    public static function render(
        object $thread,
        bool $canmanage
    ): string {
        $out = html_writer::start_tag(
            'section',
            [
                'class' => 'crm-inbox-thread',
                'aria-label' => get_string(
                    'crm_inbox_thread_region_label',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= self::render_context_panel(
            $thread,
            $canmanage
        );

        $out .= self::render_messages_panel(
            $thread
        );

        $out .= self::render_reply_panel(
            $thread,
            $canmanage
        );

        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Renders the legacy combined context panel.
     *
     * Kept as a compatibility entry point for callers that still expect
     * the former all-in-one context renderer.
     */
    public static function render_context_panel(
        object $thread,
        bool $canmanage
    ): string {
        $out = self::render_overview_panel(
            $thread
        );

        $out .= self::render_contact_panel(
            $thread
        );

        if ($canmanage) {
            $out .= self::render_actions_panel(
                $thread,
                true
            );
        }

        return $out;
    }

    /**
     * Renders the main business information about the conversation.
     */
    public static function render_overview_panel(
        object $thread
    ): string {
        $subject =
            trim((string)($thread->subject ?? ''));

        if ($subject === '') {
            $subject = get_string(
                'crm_inbox_no_subject',
                'local_subscriptions'
            );
        }

        $status =
            trim((string)($thread->status ?? ''));

        $priority =
            trim((string)($thread->priority ?? ''));

        $statuslabel = self::translated_value(
            'crm_inbox_status_',
            $status,
            'crm_inbox_status_unknown'
        );

        $prioritylabel = self::translated_value(
            'crm_inbox_priority_',
            $priority,
            'crm_inbox_priority_unknown'
        );

        $statusclass = match ($status) {
            'open' =>
                'bg-primary',

            'pending' =>
                'bg-warning text-dark',

            'resolved' =>
                'bg-success',

            'closed' =>
                'bg-secondary',

            'spam' =>
                'bg-danger',

            default =>
                'bg-light text-dark border',
        };

        $priorityclass = match ($priority) {
            'urgent' =>
                'bg-danger',

            'high' =>
                'bg-warning text-dark',

            'normal' =>
                'bg-info text-dark',

            'low' =>
                'bg-light text-dark border',

            default =>
                'bg-light text-dark border',
        };

        $headingid =
            'crm-inbox-thread-overview-' .
            (int)$thread->id;

        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'card crm-inbox-thread-context-card ' .
                    'crm-inbox-thread-overview-panel',

                'aria-labelledby' =>
                    $headingid,
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        $out .= html_writer::tag(
            'h2',
            s($subject),
            [
                'id' => $headingid,
                'class' =>
                    'h5 crm-inbox-thread-panel-title',
            ]
        );

        $out .= html_writer::div(
            html_writer::span(
                s($statuslabel),
                'badge ' . $statusclass
            )
            .
            html_writer::span(
                s($prioritylabel),
                'badge ' . $priorityclass
            ),
            'crm-inbox-thread-badges ' .
            'd-flex flex-wrap gap-2 mb-3'
        );

        $rows = [];

        if (
            trim(
                (string)($thread->accountemail ?? '')
            ) !== ''
        ) {
            $rows[] = self::detail_row(
                get_string(
                    'inbox_thread_overview_account',
                    'local_subscriptions'
                ),
                s((string)$thread->accountemail)
            );
        }

        if (
            trim(
                (string)($thread->folder ?? '')
            ) !== ''
        ) {
            $rows[] = self::detail_row(
                get_string(
                    'inbox_thread_overview_folder',
                    'local_subscriptions'
                ),
                s((string)$thread->folder)
            );
        }

        $rows[] = self::detail_row(
            get_string(
                'inbox_thread_overview_messages',
                'local_subscriptions'
            ),
            (string)(int)($thread->messagecount ?? 0)
        );

        $rows[] = self::detail_row(
            get_string(
                'inbox_thread_overview_unread',
                'local_subscriptions'
            ),
            (string)(int)($thread->unreadcount ?? 0)
        );

        $assignment =
            self::assignment_label($thread);

        $rows[] = self::detail_row(
            get_string(
                'inbox_thread_overview_assignment',
                'local_subscriptions'
            ),
            s($assignment)
        );

        $lastmessageat =
            (int)($thread->lastmessageat ?? 0);

        if ($lastmessageat > 0) {
            $rows[] = self::detail_row(
                get_string(
                    'inbox_thread_overview_last_message',
                    'local_subscriptions'
                ),
                userdate(
                    $lastmessageat,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                )
            );
        }

        $out .= html_writer::tag(
            'dl',
            implode('', $rows),
            [
                'class' =>
                    'crm-inbox-thread-detail-list mb-0',
            ]
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Renders the contact and Moodle-user matching information.
     */
    public static function render_contact_panel(
        object $thread
    ): string {
        $headingid =
            'crm-inbox-thread-contact-' .
            (int)$thread->id;

        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'card crm-inbox-thread-context-card ' .
                    'crm-inbox-thread-contact-panel',

                'aria-labelledby' =>
                    $headingid,
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        $out .= html_writer::tag(
            'h2',
            get_string(
                'inbox_thread_contact_title',
                'local_subscriptions'
            ),
            [
                'id' => $headingid,
                'class' =>
                    'h5 crm-inbox-thread-panel-title',
            ]
        );

        $contactname =
            trim(
                (string)($thread->contactname ?? '')
            );

        $contactemail =
            trim(
                (string)($thread->contactemail ?? '')
            );

        if ($contactname !== '') {
            $out .= html_writer::div(
                s($contactname),
                'crm-inbox-thread-contact-name fw-semibold'
            );
        }

        if ($contactemail !== '') {
            $out .= html_writer::link(
                'mailto:' . $contactemail,
                s($contactemail),
                [
                    'class' =>
                        'crm-inbox-thread-contact-email ' .
                        'd-inline-block mt-1',
                ]
            );
        }

        if (
            $contactname === ''
            && $contactemail === ''
        ) {
            $out .= html_writer::div(
                get_string(
                    'inbox_thread_contact_unavailable',
                    'local_subscriptions'
                ),
                'text-muted'
            );
        }

        $out .= html_writer::div(
            '',
            'crm-inbox-thread-contact-separator'
        );

        if (!empty($thread->matcheduserid)) {
            $matchedname =
                self::matched_user_fullname($thread);

            $profileurl = new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' =>
                        (int)$thread->matcheduserid,
                ]
            );

            $out .= html_writer::div(
                get_string(
                    'crm_inbox_matched_user',
                    'local_subscriptions',
                    $matchedname
                ),
                'crm-inbox-thread-match-label'
            );

            $out .= html_writer::link(
                $profileurl,
                get_string(
                    'inbox_thread_contact_open_profile',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-primary mt-3',
                ]
            );
        } else {
            $out .= html_writer::span(
                get_string(
                    'crm_inbox_external_contact',
                    'local_subscriptions'
                ),
                'badge bg-light text-dark border'
            );

            $out .= html_writer::div(
                get_string(
                    'inbox_thread_contact_external_description',
                    'local_subscriptions'
                ),
                'small text-muted mt-2'
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Renders the available business actions.
     */
    public static function render_actions_panel(
        object $thread,
        bool $canmanage
    ): string {
        if (!$canmanage) {
            return '';
        }

        $headingid =
            'crm-inbox-thread-actions-' .
            (int)$thread->id;

        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'card crm-inbox-thread-context-card ' .
                    'crm-inbox-thread-actions-panel',

                'aria-labelledby' =>
                    $headingid,
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        $out .= html_writer::tag(
            'h2',
            get_string(
                'inbox_thread_actions_title',
                'local_subscriptions'
            ),
            [
                'id' => $headingid,
                'class' =>
                    'h5 crm-inbox-thread-panel-title',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'inbox_thread_actions_description',
                'local_subscriptions'
            ),
            'small text-muted'
        );

        $out .= self::actions($thread);

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Returns a translated Inbox value without triggering an invalid
     * get_string() call when a provider returns an unknown value.
     */
    private static function translated_value(
        string $prefix,
        string $value,
        string $fallbackkey
    ): string {
        $key = $prefix . $value;

        if (
            $value !== ''
            && get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $key,
                'local_subscriptions'
            );
        }

        return get_string(
            $fallbackkey,
            'local_subscriptions'
        );
    }

    /**
     * Renders one definition-list row.
     */
    private static function detail_row(
        string $label,
        string $value
    ): string {
        return html_writer::div(
            html_writer::tag(
                'dt',
                $label,
                [
                    'class' =>
                        'crm-inbox-thread-detail-label',
                ]
            )
            .
            html_writer::tag(
                'dd',
                $value,
                [
                    'class' =>
                        'crm-inbox-thread-detail-value',
                ]
            ),
            'crm-inbox-thread-detail-row'
        );
    }

    /**
     * Returns the current user or team assignment label.
     */
    private static function assignment_label(
        object $thread
    ): string {
        $teamname =
            trim(
                (string)(
                    $thread->assignedteamname ?? ''
                )
            );

        if ($teamname !== '') {
            return get_string(
                'inbox_thread_assignment_team',
                'local_subscriptions',
                $teamname
            );
        }

        $firstname =
            trim(
                (string)(
                    $thread->assignedfirstname ?? ''
                )
            );

        $lastname =
            trim(
                (string)(
                    $thread->assignedlastname ?? ''
                )
            );

        $fullname =
            trim($firstname . ' ' . $lastname);

        if ($fullname !== '') {
            return get_string(
                'inbox_thread_assignment_user',
                'local_subscriptions',
                $fullname
            );
        }

        return get_string(
            'inbox_thread_assignment_unassigned',
            'local_subscriptions'
        );
    }

    /**
     * Builds a Moodle-compatible fullname for the matched user.
     *
     * The Inbox query currently exposes the matched user's first and last
     * names through matchedfirstname and matchedlastname. Moodle 5 expects
     * every configurable name field to exist on objects passed to fullname().
     */
    private static function matched_user_fullname(
        object $thread
    ): string {
        $user = new \stdClass();

        foreach (
            \core_user\fields::get_name_fields()
            as $field
        ) {
            $user->{$field} = '';
        }

        $user->firstname =
            (string)($thread->matchedfirstname ?? '');

        $user->lastname =
            (string)($thread->matchedlastname ?? '');

        return fullname($user);
    }

    private static function actions(
        object $thread
    ): string {
        $threadid = (int)$thread->id;

        $out = html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-thread-actions ' .
                    'd-grid gap-2 mt-3',

                'role' => 'group',

                'aria-label' => get_string(
                    'crm_inbox_thread_actions_label',
                    'local_subscriptions'
                ),
            ]
        );

        foreach ([
            'open',
            'pending',
            'resolved',
            'closed',
        ] as $status) {
            if (
                (string)$thread->status ===
                $status
            ) {
                continue;
            }

            $out .= self::action_form(
                $threadid,
                'status',
                get_string(
                    'crm_inbox_status_' . $status,
                    'local_subscriptions'
                ),
                'btn btn-sm btn-outline-secondary',
                $status
            );
        }

        $out .= self::action_form(
            $threadid,
            'archive',
            get_string(
                'crm_inbox_archive',
                'local_subscriptions'
            ),
            'btn btn-sm btn-outline-secondary'
        );

        $out .= self::action_form(
            $threadid,
            'trash',
            get_string(
                'crm_inbox_move_to_trash',
                'local_subscriptions'
            ),
            'btn btn-sm btn-outline-danger',
            null,
            get_string(
                'crm_inbox_trash_confirm',
                'local_subscriptions'
            )
        );

        if (
            \local_subscriptions\admin\AdminSecurity::can(
                \local_subscriptions\admin\Capabilities::
                    MANAGE_WORK_ITEMS
            )
        ) {
            $params = [
                'source' =>
                    \local_subscriptions\crm\work\domain\WorkItemSource::INBOX,

                'threadid' =>
                    (int)$thread->id,
            ];

            if (!empty($thread->matcheduserid)) {
                $params['targetuserid'] =
                    (int)$thread->matcheduserid;
            }

            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_work_item_create_page(),
                    $params
                ),
                get_string(
                    'crm_work_create_from_thread',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-primary',
                ]
            );
        }

        $out .= html_writer::end_tag(
            'div'
        );

        return $out;
    }

    /**
     * Renders the thread messages.
     */
    public static function render_messages_panel(
        object $thread,
        bool $allowremoteimages = false
    ): string {
        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'crm-inbox-thread-messages-panel',
                'aria-label' => get_string(
                    'crm_inbox_messages_heading',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::div(
            '',
            'visually-hidden',
            [
                'role' => 'status',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
                'data-inbox-live-region' => '1',
            ]
        );

        $messagesheadingid =
            'crm-inbox-thread-messages-' .
            (int)$thread->id;

        $out .= html_writer::tag(
            'h2',
            get_string(
                'crm_inbox_messages_heading',
                'local_subscriptions'
            ),
            [
                'id' => $messagesheadingid,
                'class' => 'visually-hidden',
            ]
        );

        $out .= html_writer::start_tag(
            'div',
            [
                'class' => 'crm-inbox-message-list',
                'role' => 'list',
                'aria-labelledby' =>
                    $messagesheadingid,
            ]
        );

        $messages = array_values(
            $thread->messages ?? []
        );

        usort(
            $messages,
            static function(
                object $left,
                object $right
            ): int {
                $leftdate = self::message_timestamp(
                    $left
                );
                $rightdate = self::message_timestamp(
                    $right
                );

                if ($leftdate !== $rightdate) {
                    return $rightdate <=> $leftdate;
                }

                return (int)($right->id ?? 0)
                    <=> (int)($left->id ?? 0);
            }
        );

        foreach ($messages as $message) {
            $out .= self::message(
                $message,
                $allowremoteimages
            );
        }

        $out .= html_writer::end_tag('div');
        $out .= html_writer::end_tag('section');

        return $out;
    }

    /**
     * Renders the main reply action.
     */
    public static function render_reply_panel(
        object $thread,
        bool $canmanage
    ): string {
        if (!$canmanage) {
            return '';
        }

        return html_writer::div(
            html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_reply_page(),
                    [
                        'threadid' =>
                            (int)$thread->id,
                    ]
                ),
                get_string(
                    'crm_inbox_reply',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-primary',
                ]
            ),
            'crm-inbox-thread-reply-panel'
        );
    }

    private static function message(
        object $message,
        bool $allowremoteimages = false
    ): string {
        $messageid =
            (int)$message->id;

        $headerid =
            'crm-inbox-message-header-' .
            $messageid;

        $direction =
            (string)$message->direction;

        $out = html_writer::start_tag(
            'article',
            [
                'class' =>
                    'card mb-3 crm-inbox-message ' .
                    (
                        $direction === 'outbound'
                            ? 'crm-inbox-message-outbound'
                            : 'crm-inbox-message-inbound'
                    ),
                
                'role' => 'listitem',

                'aria-labelledby' =>
                    $headerid,
            ]
        );

        $out .= html_writer::start_tag(
            'header',
            [
                'id' => $headerid,
                'class' => 'card-header',
            ]
        );

        $out .= html_writer::span(
            get_string(
                'crm_inbox_direction_' .
                    $message->direction,
                'local_subscriptions'
            ),
            'badge me-2 ' .
            (
                $direction === 'outbound'
                    ? 'bg-primary'
                    : 'bg-secondary'
            )
        );

        $date = self::message_timestamp(
            $message
        );

        $out .= userdate(
            $date,
            get_string(
                'strftimedatetimeshort',
                'langconfig'
            )
        );

        if ($message->status === 'draft') {
            $out .= html_writer::span(
                get_string(
                    'crm_inbox_message_status_draft',
                    'local_subscriptions'
                ),
                'badge bg-warning text-dark ms-2'
            );
        }

        $out .= html_writer::end_tag(
            'header'
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        if (
            $message->bodyhtml !== null &&
            trim((string)$message->bodyhtml) !== ''
        ) {
            $sanitizer = new InboxHtmlSanitizer();

            $safehtml = $sanitizer->sanitize(
                (string)$message->bodyhtml,
                $allowremoteimages
            );

            $out .= html_writer::div(
                $safehtml,
                'crm-inbox-message-html',
                [
                    'aria-label' => get_string(
                        'crm_inbox_message_content_label',
                        'local_subscriptions'
                    ),
                ]
            );
        } else {
            $out .= html_writer::tag(
                'div',
                nl2br(
                    s((string)$message->bodytext)
                ),
                [
                    'class' =>
                        'crm-inbox-message-text',

                    'aria-label' => get_string(
                        'crm_inbox_message_content_label',
                        'local_subscriptions'
                    ),
                ]
            );
        }

        if (!empty($message->attachments)) {
            $out .= html_writer::start_tag(
                'div',
                [
                    'class' =>
                        'crm-inbox-attachments mt-3',

                    'role' => 'group',

                    'aria-label' => get_string(
                        'crm_inbox_attachments_label',
                        'local_subscriptions'
                    ),
                ]
            );

            foreach (
                $message->attachments
                as $attachment
            ) {
                if (
                    $attachment->downloadstatus === 'stored' &&
                    !empty($attachment->fileitemid)
                ) {
                    $out .= html_writer::link(
                        subscription_config::
                            inbox_attachment_url(
                                (int)$attachment->fileitemid,
                                (string)$attachment->filename
                            ),
                        s((string)$attachment->filename),
                        [
                            'class' =>
                                'btn btn-sm btn-outline-secondary ' .
                                'me-2 mb-2 crm-inbox-attachment-link',

                            'download' =>
                                (string)$attachment->filename,

                            'aria-label' => get_string(
                                'crm_inbox_download_attachment',
                                'local_subscriptions',
                                (string)$attachment->filename
                            ),
                        ]
                    );
                } else {
                    $attachmentname =
                        (string)$attachment->filename;

                    $out .= html_writer::span(
                        s($attachmentname) .
                        html_writer::span(
                            ' — ' .
                            get_string(
                                'crm_inbox_attachment_unavailable',
                                'local_subscriptions'
                            ),
                            'visually-hidden'
                        ),
                        'badge bg-light text-dark border ' .
                        'me-2 crm-inbox-attachment-unavailable',
                        [
                            'aria-label' =>
                                get_string(
                                    'crm_inbox_attachment_unavailable_aria',
                                    'local_subscriptions',
                                    $attachmentname
                                ),
                        ]
                    );
                }
            }

            $out .= html_writer::end_tag(
                'div'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::end_tag(
            'article'
        );

        return $out;
    }

    private static function message_timestamp(
        object $message
    ): int {
        foreach (
            [
                'receivedat',
                'sentat',
                'timecreated',
            ]
            as $field
        ) {
            $value = (int)($message->{$field} ?? 0);

            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    private static function action_form(
        int $threadid,
        string $action,
        string $label,
        string $class,
        ?string $value = null,
        ?string $confirmation = null
    ): string {
        $attributes = [
            'method' => 'post',

            'action' =>
                subscription_config::
                    admin_inbox_action_page(),

            'class' =>
                'd-inline-block crm-inbox-action-form',

            'data-inbox-busy-form' => '1',

            'data-busy-announcement' =>
                get_string(
                    'crm_inbox_action_processing',
                    'local_subscriptions'
                ),
        ];

        if ($confirmation !== null) {
            $attributes['data-inbox-confirm'] =
                $confirmation;
        }

        $out = html_writer::start_tag(
            'form',
            $attributes
        );

        foreach ([
            'sesskey' => sesskey(),
            'id' => $threadid,
            'action' => $action,
        ] as $name => $fieldvalue) {
            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $fieldvalue,
                ]
            );
        }

        if ($value !== null) {
            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'value',
                    'value' => $value,
                ]
            );
        }

        $out .= html_writer::tag(
            'button',
            $label,
            [
                'type' => 'submit',

                'class' => $class,

                'data-loading-label' =>
                    get_string(
                        'crm_inbox_processing',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::end_tag(
            'form'
        );

        return $out;
    }

}