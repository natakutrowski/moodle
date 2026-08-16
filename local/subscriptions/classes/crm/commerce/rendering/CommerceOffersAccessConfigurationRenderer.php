<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Shared UX primitives for guided Offers & access configuration.
 */
final class CommerceOffersAccessConfigurationRenderer {
    public static function start_layout(): string {
        return html_writer::start_div('crm-offers-access-config-layout');
    }

    public static function end_layout(): string {
        return html_writer::end_div();
    }

    public static function start_main(): string {
        return html_writer::start_div('crm-offers-access-config-main');
    }

    public static function end_main(): string {
        return html_writer::end_div();
    }

    public static function start_section(
        string $title,
        string $help = '',
        string $icon = 'fa-sliders'
    ): string {
        $heading = html_writer::div(
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon,
                    'aria-hidden' => 'true',
                ]),
                'crm-offers-access-config-section-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'h2',
                    s($title),
                    ['class' => 'crm-offers-access-config-section-title']
                )
                . ($help !== ''
                    ? html_writer::div(
                        s($help),
                        'crm-offers-access-config-section-help'
                    )
                    : ''),
                'crm-offers-access-config-section-copy'
            ),
            'crm-offers-access-config-section-heading'
        );

        return html_writer::start_div('crm-offers-access-config-section')
            . $heading;
    }

    public static function end_section(): string {
        return html_writer::end_div();
    }

    public static function advanced(
        string $title,
        string $content,
        string $hint = ''
    ): string {
        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-sliders',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    s($title),
                    'crm-offers-access-advanced-title'
                )
                . ($hint !== ''
                    ? html_writer::span(
                        s($hint),
                        'crm-offers-access-advanced-hint'
                    )
                    : ''),
                ['class' => 'crm-offers-access-advanced-summary']
            )
            . html_writer::div(
                $content,
                'crm-offers-access-advanced-body'
            ),
            ['class' => 'crm-offers-access-advanced']
        );
    }

    /**
     * @param array<int,array{label:string,value:string,id?:string,class?:string}> $rows
     */
    public static function summary(
        string $title,
        array $rows,
        string $kind,
        ?moodle_url $mailstudiourl = null
    ): string {
        $content = html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($kind === 'grant' ? 'fa-key' : 'fa-tag'),
                'aria-hidden' => 'true',
            ])
            . html_writer::tag(
                'strong',
                s($title)
            ),
            'crm-offers-access-config-summary-title is-' . $kind
        );

        foreach ($rows as $row) {
            $attributes = [];
            if (!empty($row['id'])) {
                $attributes['id'] = $row['id'];
            }
            $valueclass = 'crm-offers-access-config-summary-value';
            if (!empty($row['class'])) {
                $valueclass .= ' ' . $row['class'];
            }
            $content .= html_writer::div(
                html_writer::span(
                    s($row['label']),
                    'crm-offers-access-config-summary-label'
                )
                . html_writer::span(
                    s($row['value']),
                    $valueclass,
                    $attributes
                ),
                'crm-offers-access-config-summary-row'
            );
        }

        if ($mailstudiourl !== null) {
            $content .= html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-envelope-o',
                        'aria-hidden' => 'true',
                    ])
                    . get_string(
                        'commerce_offers_access_config_mailstudio_title',
                        'local_subscriptions'
                    ),
                    'crm-offers-access-config-mail-title'
                )
                . html_writer::div(
                    get_string(
                        'commerce_offers_access_config_mailstudio_help',
                        'local_subscriptions'
                    ),
                    'crm-offers-access-config-mail-help'
                )
                . html_writer::link(
                    $mailstudiourl,
                    get_string(
                        'commerce_offers_access_config_open_mailstudio',
                        'local_subscriptions'
                    ),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'target' => '_blank',
                        'rel' => 'noopener',
                    ]
                ),
                'crm-offers-access-config-mail'
            );
        }

        return html_writer::div(
            $content,
            'crm-offers-access-config-summary'
        );
    }

    private function __construct() {}
}
