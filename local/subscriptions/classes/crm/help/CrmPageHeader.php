<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

use html_writer;

final class CrmPageHeader {

    public static function render(
        string $title,
        ?string $subtitle = null,
        ?string $helpcontext = null,
        ?string $actions = null
    ): string {
        $out = html_writer::start_div(
            'crm-page-header'
        );

        $out .= html_writer::start_div(
            'crm-page-header-main'
        );

        $out .= html_writer::tag(
            'h2',
            s($title),
            [
                'class' => 'crm-page-header-title',
            ]
        );

        if ($subtitle !== null && $subtitle !== '') {
            $out .= html_writer::div(
                s($subtitle),
                'crm-page-header-subtitle'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'crm-page-header-tools'
        );

        if ($actions !== null && $actions !== '') {
            $out .= html_writer::div(
                $actions,
                'crm-page-header-actions'
            );
        }

        $out .= HelpContextPanel::render(
            $helpcontext
                ?? HelpContextResolver::current()
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }
}