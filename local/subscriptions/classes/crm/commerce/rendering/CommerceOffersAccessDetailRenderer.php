<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Shared business-first detail layout for individual offers and access grants.
 */
final class CommerceOffersAccessDetailRenderer {
    /**
     * @param array<int,array{label:string,value:string,html?:bool,class?:string}> $facts
     */
    public static function hero(
        string $kind,
        string $title,
        string $subtitle,
        string $status,
        string $statusclass,
        array $facts
    ): string {
        $icon = $kind === 'grant' ? 'fa-key' : 'fa-tag';
        $facthtml = [];
        foreach ($facts as $fact) {
            $value = !empty($fact['html'])
                ? $fact['value']
                : s($fact['value']);
            $class = 'crm-offers-access-detail-fact';
            if (!empty($fact['class'])) {
                $class .= ' ' . $fact['class'];
            }
            $facthtml[] = html_writer::div(
                html_writer::div(
                    s($fact['label']),
                    'crm-offers-access-detail-fact-label'
                )
                . html_writer::div(
                    $value,
                    'crm-offers-access-detail-fact-value'
                ),
                $class
            );
        }

        return html_writer::div(
            html_writer::div(
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa ' . $icon,
                        'aria-hidden' => 'true',
                    ]),
                    'crm-offers-access-detail-icon is-' . $kind
                )
                . html_writer::div(
                    html_writer::div(
                        s($title),
                        'crm-offers-access-detail-title'
                    )
                    . html_writer::div(
                        s($subtitle),
                        'crm-offers-access-detail-subtitle'
                    ),
                    'crm-offers-access-detail-heading-copy'
                )
                . html_writer::span(
                    s($status),
                    'crm-offers-access-status ' . $statusclass
                ),
                'crm-offers-access-detail-heading'
            )
            . html_writer::div(
                implode('', $facthtml),
                'crm-offers-access-detail-facts'
            ),
            'crm-offers-access-detail-hero is-' . $kind
        );
    }

    public static function panel(
        string $title,
        string $content,
        string $icon = 'fa-info-circle',
        string $class = ''
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon,
                    'aria-hidden' => 'true',
                ])
                . html_writer::tag('h2', s($title)),
                'crm-offers-access-detail-panel-title'
            )
            . $content,
            trim('crm-offers-access-detail-panel ' . $class)
        );
    }

    /**
     * @param array<int,array{label:string,value:string,html?:bool}> $rows
     */
    public static function rows(array $rows): string {
        $html = [];
        foreach ($rows as $row) {
            $value = !empty($row['html'])
                ? $row['value']
                : s($row['value']);
            $html[] = html_writer::div(
                html_writer::div(
                    s($row['label']),
                    'crm-offers-access-detail-row-label'
                )
                . html_writer::div(
                    $value,
                    'crm-offers-access-detail-row-value'
                ),
                'crm-offers-access-detail-row'
            );
        }
        return html_writer::div(
            implode('', $html),
            'crm-offers-access-detail-rows'
        );
    }

    /**
     * @param array<int,array{label:string,url:\moodle_url,class?:string,icon?:string}> $actions
     */
    public static function actions(array $actions): string {
        $html = [];
        foreach ($actions as $action) {
            $class = $action['class'] ?? 'btn btn-outline-secondary';
            $icon = $action['icon'] ?? 'fa-arrow-right';
            $html[] = html_writer::link(
                $action['url'],
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon . ' me-1',
                    'aria-hidden' => 'true',
                ])
                . s($action['label']),
                ['class' => $class]
            );
        }

        return html_writer::div(
            implode('', $html),
            'crm-offers-access-detail-actions'
        );
    }

    public static function technical(
        string $title,
        string $content,
        bool $open = false
    ): string {
        $attributes = ['class' => 'crm-offers-access-detail-technical'];
        if ($open) {
            $attributes['open'] = 'open';
        }

        return html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-code',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    s($title),
                    'crm-offers-access-detail-technical-title'
                ),
                ['class' => 'crm-offers-access-detail-technical-summary']
            )
            . html_writer::div(
                $content,
                'crm-offers-access-detail-technical-body'
            ),
            $attributes
        );
    }

    private function __construct() {}
}
