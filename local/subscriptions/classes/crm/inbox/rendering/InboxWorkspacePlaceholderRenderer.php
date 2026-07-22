<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Renders the permanent Inbox preview regions and their initial states.
 */
final class InboxWorkspacePlaceholderRenderer {

    /**
     * Renders the permanent conversation reading region.
     */
    public static function reading(): string {
        return html_writer::tag(
            'section',
            self::reading_placeholder_content(),
            [
                'class' =>
                    'crm-inbox-preview-region ' .
                    'crm-inbox-preview-reading-region',

                'data-region' =>
                    'inbox-reading-panel',

                'aria-label' =>
                    get_string(
                        'crm_inbox_preview_reading_region',
                        'local_subscriptions'
                    ),

                'aria-busy' => 'false',

                'tabindex' => '-1',
            ]
        );
    }

    /**
     * Renders the permanent customer context region.
     */
    public static function context(): string {
        return html_writer::tag(
            'aside',
            self::context_placeholder_content(),
            [
                'class' =>
                    'crm-inbox-preview-region ' .
                    'crm-inbox-preview-context-region',

                'data-region' =>
                    'inbox-context-panel',

                'aria-label' =>
                    get_string(
                        'crm_inbox_preview_context_region',
                        'local_subscriptions'
                    ),

                'aria-busy' => 'false',
            ]
        );
    }

    /**
     * Returns the initial reading placeholder.
     */
    private static function reading_placeholder_content():
        string {
        return self::placeholder(
            '✉️',
            get_string(
                'inbox_workspace_reading_placeholder_title',
                'local_subscriptions'
            ),
            get_string(
                'inbox_workspace_reading_placeholder_description',
                'local_subscriptions'
            ),
            'crm-inbox-workspace-reading-placeholder'
        );
    }

    /**
     * Returns the initial context placeholder.
     */
    private static function context_placeholder_content():
        string {
        return self::placeholder(
            '👤',
            get_string(
                'inbox_workspace_context_placeholder_title',
                'local_subscriptions'
            ),
            get_string(
                'inbox_workspace_context_placeholder_description',
                'local_subscriptions'
            ),
            'crm-inbox-workspace-context-placeholder'
        );
    }

    /**
     * Renders one initial placeholder.
     */
    private static function placeholder(
        string $icon,
        string $title,
        string $description,
        string $extraclass
    ): string {
        $content = html_writer::span(
            $icon,
            'crm-inbox-workspace-placeholder-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $content .= html_writer::tag(
            'h2',
            $title,
            [
                'class' =>
                    'crm-inbox-workspace-placeholder-title',
            ]
        );

        $content .= html_writer::tag(
            'p',
            $description,
            [
                'class' =>
                    'crm-inbox-workspace-placeholder-description',
            ]
        );

        return html_writer::div(
            $content,
            'crm-inbox-workspace-placeholder ' .
            $extraclass,
            [
                'data-region' =>
                    'inbox-preview-placeholder',

                'role' => 'status',
            ]
        );
    }
}