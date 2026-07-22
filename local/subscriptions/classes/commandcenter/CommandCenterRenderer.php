<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

final class CommandCenterRenderer {

    /**
     * Renders the Command Center.
     *
     * The visible trigger may be omitted when the Command Center is hosted by
     * the autonomous CRM shell. A hidden technical trigger is still rendered
     * because the existing JavaScript state expects one.
     *
     * @param bool $showtrigger Whether to display the large search trigger.
     * @return string
     */
    public static function render(
        bool $showtrigger = true
    ): string {
        $searchurl = (new moodle_url(subscription_config::command_center_search_ajax()))->out(false);

        $out = '';

        $rootclasses = [
            'campusfr-command-center',
        ];

        if ($showtrigger) {
            $rootclasses[] = 'mb-4';
        } else {
            $rootclasses[] =
                'campusfr-command-center--shell';
        }

        $out .= html_writer::start_div(
            implode(' ', $rootclasses),
            [
                'data-search-url' => $searchurl,
                'data-execute-url' => (new moodle_url(subscription_config::command_center_execute_ajax()))->out(false),
                'data-empty-label' => get_string('command_center_empty', 'local_subscriptions'),
                'data-error-label' => get_string('command_center_error', 'local_subscriptions'),
                'data-loading-label' => get_string('command_center_loading', 'local_subscriptions'),
                'data-initial-label' => get_string('command_center_initial', 'local_subscriptions'),
                'data-recent-label' => get_string('command_center_recent', 'local_subscriptions'),
                'data-favorite-label' => get_string('command_center_favorites', 'local_subscriptions'),
                'data-favorite-title' => get_string('command_center_favorite_toggle', 'local_subscriptions'),
                'data-clear-recent-label' => get_string('command_center_clear_recent', 'local_subscriptions'),
                'data-action-error-label' => get_string('command_center_action_error', 'local_subscriptions'),
                'data-action-failed-label' => get_string('command_center_action_failed', 'local_subscriptions'),
                'data-confirm-label' => get_string('command_center_confirm', 'local_subscriptions'),
                'data-cancel-label' => get_string('command_center_cancel', 'local_subscriptions'),
                'data-danger-confirm-label' => get_string('command_center_danger_confirm', 'local_subscriptions'),
                'data-menu-actions-label' =>
                    get_string(
                        'command_center_menu_actions',
                        'local_subscriptions'
                    ),

                'data-dialog-label' =>
                    get_string(
                        'command_center_confirmation_dialog',
                        'local_subscriptions'
                    ),
            ]
        );

        $triggerclasses = [
            'campusfr-command-trigger',
        ];

        $triggerattributes = [
            'role' =>
                'button',

            'tabindex' =>
                $showtrigger
                    ? '0'
                    : '-1',

            'aria-label' =>
                get_string(
                    'command_center_open',
                    'local_subscriptions'
                ),
        ];

        if (!$showtrigger) {
            $triggerclasses[] =
                'campusfr-command-trigger--shell-proxy';

            $triggerattributes['aria-hidden'] =
                'true';
        }

        $triggerattributes['class'] =
            implode(
                ' ',
                $triggerclasses
            );

        $out .= html_writer::start_tag(
            'div',
            $triggerattributes
        );

        if ($showtrigger) {
            $out .= html_writer::span(
                'Ctrl / ⌘ K · Ctrl Alt K',
                'campusfr-command-shortcut'
            );

            $out .= html_writer::span(
                get_string(
                    'command_center_placeholder',
                    'local_subscriptions'
                ),
                'campusfr-command-placeholder'
            );
        }

        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_div(
            'campusfr-command-modal d-none',
            [
                'role' => 'dialog',

                'aria-modal' => 'true',

                'aria-hidden' => 'true',

                'aria-label' =>
                    get_string(
                        'command_center_open',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::start_div('campusfr-command-backdrop');
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('campusfr-command-panel');

        $out .= html_writer::tag(
            'button',
            html_writer::span(
                '×',
                '',
                [
                    'aria-hidden' => 'true',
                ]
            ),
            [
                'type' => 'button',

                'class' =>
                    'campusfr-command-close',

                'aria-label' =>
                    get_string(
                        'command_center_close',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::tag('input', '', [
            'type' => 'text',
            'class' => 'campusfr-command-input',
            'placeholder' => get_string('command_center_input_placeholder', 'local_subscriptions'),
            'autocomplete' => 'off',
            'role' => 'combobox',
            'aria-expanded' => 'true',
            'aria-autocomplete' => 'list',
            'aria-controls' => 'campusfr-command-results',
        ]);

        $out .= html_writer::div(
            html_writer::tag('span', '↑↓', ['class' => 'campusfr-command-kbd']) . ' ' .
            get_string('command_center_hint_navigate', 'local_subscriptions') . ' ' .
            html_writer::tag('span',
                get_string('command_center_key_enter', 'local_subscriptions'),
                ['class' => 'campusfr-command-kbd']
            ) . ' ' .
            get_string('command_center_hint_open', 'local_subscriptions') . ' ' .
            html_writer::tag('span',
                get_string('command_center_key_escape', 'local_subscriptions'),
                ['class' => 'campusfr-command-kbd']
            ) . ' ' .
            get_string('command_center_hint_close', 'local_subscriptions'),
            'campusfr-command-input-hint'
        );

        $out .= html_writer::div('', 'campusfr-command-results', [
            'id' => 'campusfr-command-results',
            'role' => 'listbox',
        ]);

        $out .= html_writer::div(
            get_string('command_center_hint', 'local_subscriptions'),
            'campusfr-command-hint'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        $out .= html_writer::end_div();

        return $out;
    }
}