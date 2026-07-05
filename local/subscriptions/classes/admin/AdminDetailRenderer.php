<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use html_table;
use html_table_cell;
use html_table_row;
use html_writer;

final class AdminDetailRenderer {

    public static function card(string $title, array $rows, string $classes = ''): string {
        $out = html_writer::start_div('card card-body mb-4 ' . $classes);

        $out .= html_writer::tag('h3', $title, [
            'class' => 'mb-3',
        ]);

        $out .= self::table($rows);

        $out .= html_writer::end_div();

        return $out;
    }

    public static function table(array $rows): string {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable crm-detail-table';

        foreach ($rows as $label => $value) {
            if ($value === '') {
                $cell = new html_table_cell(html_writer::tag('strong', $label));
                $cell->colspan = 2;
                $cell->attributes['class'] = 'table-secondary text-uppercase small';

                $table->data[] = new html_table_row([$cell]);
                continue;
            }

            $table->data[] = [
                html_writer::tag('strong', $label),
                $value,
            ];
        }

        return html_writer::table($table);
    }

    public static function json(?string $json): string {
        if (empty($json)) {
            return '-';
        }

        $decoded = json_decode((string)$json, true);

        if (is_array($decoded)) {
            return html_writer::tag(
                'pre',
                s(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                ['class' => 'crm-json-preview']
            );
        }

        return html_writer::tag('pre', s((string)$json), [
            'class' => 'crm-json-preview',
        ]);
    }

    public static function pre(?string $value): string {
        if (empty($value)) {
            return '-';
        }

        return html_writer::tag('pre', s((string)$value), [
            'class' => 'crm-json-preview',
        ]);
    }

    public static function external_link(?string $url): string {
        if (empty($url)) {
            return '-';
        }

        return html_writer::link(
            $url,
            get_string('openlinkinnewwindow', 'local_subscriptions'),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        );
    }

    public static function bool_yes_no($value): string {
        return !empty($value) ? get_string('yes') : get_string('no');
    }

    public static function dash($value): string {
        $value = trim((string)$value);

        return $value !== '' ? s($value) : '-';
    }
}