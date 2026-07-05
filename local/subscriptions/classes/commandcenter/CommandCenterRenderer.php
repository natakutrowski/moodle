<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

final class CommandCenterRenderer {

    public static function render(): string {
        $searchurl = (new moodle_url(subscription_config::command_center_search_ajax()))->out(false);

        $out = '';

        $out .= html_writer::start_div('campusfr-command-center mb-4', [
            'data-search-url' => $searchurl,
            'data-empty-label' => get_string('command_center_empty', 'local_subscriptions'),
            'data-error-label' => get_string('command_center_error', 'local_subscriptions'),
            'data-loading-label' => get_string('command_center_loading', 'local_subscriptions'),
        ]);

        $out .= html_writer::start_div('campusfr-command-trigger', [
            'role' => 'button',
            'tabindex' => '0',
            'aria-label' => get_string('command_center_open', 'local_subscriptions'),
        ]);

        $out .= html_writer::span('Ctrl / ⌘ K · Ctrl Alt K', 'campusfr-command-shortcut');
        $out .= html_writer::span(get_string('command_center_placeholder', 'local_subscriptions'), 'campusfr-command-placeholder');

        $out .= html_writer::end_div();

        $out .= html_writer::start_div('campusfr-command-modal d-none', [
            'aria-hidden' => 'true',
        ]);

        $out .= html_writer::start_div('campusfr-command-backdrop');
        $out .= html_writer::end_div();

        $out .= html_writer::start_div('campusfr-command-panel');

        $out .= html_writer::tag('button', '×', [
            'type' => 'button',
            'class' => 'campusfr-command-close',
            'aria-label' => get_string('command_center_close', 'local_subscriptions'),
        ]);

        $out .= html_writer::tag('input', '', [
            'type' => 'text',
            'class' => 'campusfr-command-input',
            'placeholder' => get_string('command_center_input_placeholder', 'local_subscriptions'),
            'autocomplete' => 'off',
        ]);

        $out .= html_writer::div('', 'campusfr-command-results');

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