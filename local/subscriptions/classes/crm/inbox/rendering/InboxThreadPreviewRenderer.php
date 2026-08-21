<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders a lightweight Inbox thread preview.
 *
 * The preview deliberately excludes reply forms, AI controls and
 * Workspace personalization. Advanced actions remain available on
 * the complete thread page.
 */
final class InboxThreadPreviewRenderer {

    /**
     * Renders the central reading area.
     */
    public static function render_reading(
        object $thread,
        bool $allowremoteimages = false
    ): string {
        $threadid = (int)$thread->id;

        $threadurl = new moodle_url(
            subscription_config::
                admin_inbox_thread_page(),
            [
                'id' => $threadid,
            ]
        );

        $out = html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-preview-reading-content',

                'data-thread-id' =>
                    $threadid,
            ]
        );

        $out .= self::render_preview_header(
            $thread,
            $threadurl
        );

        $out .= InboxThreadRenderer::
            render_messages_panel(
                $thread,
                $allowremoteimages
            );

        $out .= html_writer::end_tag(
            'div'
        );

        return $out;
    }

    /**
     * Renders the customer context area.
     */
    public static function render_context(
        object $thread,
        bool $canmanage
    ): string {
        $out = html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-preview-context-content',

                'data-thread-id' =>
                    (int)$thread->id,
            ]
        );

        $out .= InboxThreadRenderer::
            render_overview_panel(
                $thread
            );

        $out .= InboxThreadRenderer::
            render_contact_panel(
                $thread
            );

        /*
         * We deliberately do not render the management action forms
         * in the AJAX preview. They remain available on thread.php.
         */
        if ($canmanage) {
            $out .= self::render_management_link(
                $thread
            );
        }

        $out .= html_writer::end_tag(
            'div'
        );

        return $out;
    }

    /**
     * Renders the preview heading and complete-view action.
     */
    private static function render_preview_header(
        object $thread,
        moodle_url $threadurl
    ): string {
        $subject =
            trim(
                (string)($thread->subject ?? '')
            );

        if ($subject === '') {
            $subject = get_string(
                'crm_inbox_no_subject',
                'local_subscriptions'
            );
        }

        $headingid =
            'crm-inbox-preview-heading-' .
            (int)$thread->id;

        $out = html_writer::start_tag(
            'header',
            [
                'class' =>
                    'crm-inbox-preview-header',
            ]
        );

        $out .= html_writer::tag(
            'h2',
            s($subject),
            [
                'id' => $headingid,

                'class' =>
                    'crm-inbox-preview-title',
            ]
        );

        $out .= html_writer::link(
            $threadurl,
            get_string(
                'crm_inbox_preview_open_full',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm ' .
                    'btn-outline-primary ' .
                    'crm-inbox-preview-open',
            ]
        );

        $out .= html_writer::end_tag(
            'header'
        );

        return $out;
    }

    /**
     * Renders the shortcut to the complete management view.
     */
    private static function render_management_link(
        object $thread
    ): string {
        $threadurl = new moodle_url(
            subscription_config::
                admin_inbox_thread_page(),
            [
                'id' => (int)$thread->id,
            ]
        );

        return html_writer::div(
            html_writer::link(
                $threadurl,
                get_string(
                    'crm_inbox_preview_manage',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-primary w-100',
                ]
            ),
            'crm-inbox-preview-management'
        );
    }
}