<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Final shared polish primitives for the Offers & access workspace.
 */
final class CommerceOffersAccessPolishRenderer {
    public static function empty_state(
        string $title,
        string $description,
        string $icon = 'fa-inbox',
        ?moodle_url $actionurl = null,
        string $actionlabel = ''
    ): string {
        $action = '';
        if ($actionurl !== null && $actionlabel !== '') {
            $action = html_writer::link(
                $actionurl,
                s($actionlabel),
                ['class' => 'btn btn-sm btn-outline-primary']
            );
        }

        return html_writer::div(
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon,
                    'aria-hidden' => 'true',
                ]),
                'crm-offers-access-empty-icon'
            )
            . html_writer::tag(
                'h2',
                s($title),
                ['class' => 'crm-offers-access-empty-title']
            )
            . html_writer::div(
                s($description),
                'crm-offers-access-empty-description'
            )
            . $action,
            'crm-offers-access-empty'
        );
    }

    /**
     * Render active filters as compact removable pills.
     *
     * @param array<int,array{label:string,value:string,url:moodle_url}> $filters
     */
    public static function filter_pills(array $filters): string {
        if ($filters === []) {
            return '';
        }

        $pills = [];
        foreach ($filters as $filter) {
            $pills[] = html_writer::link(
                $filter['url'],
                html_writer::span(
                    s($filter['label']) . ': ',
                    'crm-offers-access-filter-pill-label'
                )
                . html_writer::span(
                    s($filter['value']),
                    'crm-offers-access-filter-pill-value'
                )
                . html_writer::span(
                    '×',
                    'crm-offers-access-filter-pill-remove',
                    ['aria-hidden' => 'true']
                ),
                [
                    'class' => 'crm-offers-access-filter-pill',
                    'title' => get_string(
                        'commerce_offers_access_remove_filter',
                        'local_subscriptions'
                    ),
                ]
            );
        }

        return html_writer::div(
            implode('', $pills),
            'crm-offers-access-filter-pills'
        );
    }

    private function __construct() {}
}
