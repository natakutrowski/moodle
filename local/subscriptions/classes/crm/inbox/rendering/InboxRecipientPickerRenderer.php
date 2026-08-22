<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

final class InboxRecipientPickerRenderer {

    public static function render(
        string $name,
        string $label,
        string $value = '',
        bool $required = false,
        array $labels = []
    ): string {
        $id = 'id_' . $name;

        return html_writer::div(
            html_writer::label(
                $label,
                $id . '_search',
                false,
                ['class' => 'form-label']
            )
            . html_writer::div(
                html_writer::div(
                    '',
                    'crm-inbox-recipient-pills',
                    [
                        'data-inbox-recipient-pills' => '1',
                    ]
                )
                . html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'text',
                        'id' => $id . '_search',
                        'class' =>
                            'crm-inbox-recipient-search',
                        'autocomplete' => 'off',
                        'placeholder' => get_string(
                            'crm_inbox_o16_3_recipient_placeholder',
                            'local_subscriptions'
                        ),
                        'role' => 'combobox',
                        'aria-autocomplete' => 'list',
                        'aria-expanded' => 'false',
                        'data-inbox-recipient-search' => '1',
                    ]
                ),
                'crm-inbox-recipient-shell'
            )
            . html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $name,
                    'id' => $id,
                    'value' => $value,
                    'data-inbox-recipient-value' => '1',
                ]
            )
            . html_writer::div(
                '',
                'crm-inbox-recipient-results d-none',
                [
                    'data-inbox-recipient-results' => '1',
                    'role' => 'listbox',
                ]
            ),
            'crm-inbox-recipient-picker',
            [
                'data-inbox-recipient-picker' => '1',
                'data-recipient-name' => $name,
                'data-recipient-required' =>
                    $required ? '1' : '0',
                'data-recipient-labels' => json_encode(
                    $labels,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ) ?: '{}',
                'data-recipient-search-url' => (
                    new moodle_url(
                        '/local/subscriptions/ajax/'
                        . 'inbox_recipient_search.php'
                    )
                )->out(false),
            ]
        );
    }
}
