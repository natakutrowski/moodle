<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxThreadRenderer {

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

        $out .= self::header(
            $thread,
            $canmanage
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
                'id' =>
                    $messagesheadingid,

                'class' =>
                    'visually-hidden',
            ]
        );

        $out .= html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-message-list',

                'role' => 'list',

                'aria-labelledby' =>
                    $messagesheadingid,
            ]
        );

        foreach ($thread->messages as $message) {
            $out .= self::message($message);
        }

        $out .= html_writer::end_tag(
            'div'
        );

        if ($canmanage) {
            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_reply_page(),
                    ['threadid' => (int)$thread->id]
                ),
                get_string(
                    'crm_inbox_reply',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-primary mt-3',
                ]
            );
        }

        $out .= html_writer::end_tag(
            'section'
        );

        return $out;
    }

    private static function header(
        object $thread,
        bool $canmanage
    ): string {
        $out = html_writer::start_tag(
            'header',
            [
                'class' =>
                    'card mb-3 crm-inbox-thread-header',
            ]
        );

        $out .= html_writer::start_div(
            'card-body'
        );

        $out .= html_writer::tag(
            'h2',
            s(
                trim((string)$thread->subject) !== ''
                    ? (string)$thread->subject
                    : get_string(
                        'crm_inbox_no_subject',
                        'local_subscriptions'
                    )
            ),
            ['class' => 'h4']
        );

        if (!empty($thread->contactemail)) {
            $out .= html_writer::div(
                s(
                    (string)($thread->contactname ?? '')
                ) .
                ' &lt;' .
                s((string)$thread->contactemail) .
                '&gt;',
                'text-muted'
            );
        }

        if (!empty($thread->matcheduserid)) {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_matched_user',
                    'local_subscriptions',
                    fullname((object)[
                        'firstname' =>
                            $thread->matchedfirstname,
                        'lastname' =>
                            $thread->matchedlastname,
                    ])
                ),
                'mt-2'
            );
        } else {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_external_contact',
                    'local_subscriptions'
                ),
                'badge bg-light text-dark mt-2'
            );
        }

        if ($canmanage) {
            $out .= self::actions($thread);
        }

        $out .= html_writer::end_div();

        $out .= html_writer::end_tag(
            'header'
        );

        return $out;
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
                    'd-flex flex-wrap gap-2 mt-3',

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

    private static function message(
        object $message
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

        $date = $message->receivedat
            ?? $message->sentat
            ?? $message->timecreated;

        $out .= userdate(
            (int)$date,
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
                (string)$message->bodyhtml
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