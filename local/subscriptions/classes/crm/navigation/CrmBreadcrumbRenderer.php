<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Renders lightweight breadcrumbs inside the CRM application shell.
 */
final class CrmBreadcrumbRenderer {

    /**
     * Renders a CRM breadcrumb.
     *
     * Each item must contain:
     *
     * - label: visible item label;
     * - url: moodle_url for linked ancestors, null for the current page.
     *
     * @param array<int, array{
     *     label: string,
     *     url: moodle_url|null
     * }> $items
     * @return string
     */
    public static function render(
        array $items
    ): string {
        $normalised = [];

        foreach ($items as $item) {
            $label = trim(
                (string)(
                    $item['label'] ?? ''
                )
            );

            if ($label === '') {
                continue;
            }

            $url = $item['url'] ?? null;

            if (
                $url !== null &&
                !$url instanceof moodle_url
            ) {
                throw new \coding_exception(
                    'CRM breadcrumb URLs must be moodle_url instances.'
                );
            }

            $normalised[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        if (count($normalised) < 2) {
            return '';
        }

        $lastindex =
            count($normalised) - 1;

        $listitems = [];

        foreach (
            $normalised as $index => $item
        ) {
            $iscurrent =
                $index === $lastindex;

            if (
                !$iscurrent &&
                $item['url'] instanceof moodle_url
            ) {
                $content = html_writer::link(
                    $item['url'],
                    s($item['label']),
                    [
                        'class' =>
                            'crm-breadcrumb-link',
                    ]
                );
            } else {
                $content = html_writer::span(
                    s($item['label']),
                    'crm-breadcrumb-current',
                    $iscurrent
                        ? [
                            'aria-current' =>
                                'page',
                        ]
                        : []
                );
            }

            $listitems[] = html_writer::tag(
                'li',
                $content,
                [
                    'class' =>
                        'crm-breadcrumb-item' .
                        (
                            $iscurrent
                                ? ' is-current'
                                : ''
                        ),
                ]
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::tag(
                'ol',
                implode(
                    '',
                    $listitems
                ),
                [
                    'class' =>
                        'crm-breadcrumb-list',
                ]
            ),
            [
                'class' =>
                    'crm-breadcrumb',

                'aria-label' =>
                    get_string(
                        'crm_breadcrumb_navigation',
                        'local_subscriptions'
                    ),
            ]
        );
    }
}